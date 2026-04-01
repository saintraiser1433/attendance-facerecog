<?php
// enroll_student_fingerprint.php
// Handle AJAX enrollment request FIRST - before any includes or HTML
if (isset($_POST['ajax_enroll'])) {
    // Session is already started in dashboard.php
    // Check if user is admin
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit();
    }
    
    // Include fingerprint processing functions
    require_once("../core/querydb.php");
    require_once("../core/helpers/helpers.php");
    
    // Clean any output before JSON (catch any output from includes)
    ob_clean();
    
    // Suppress any warnings/errors that might have been output
    error_reporting(0);
    ini_set('display_errors', 0);
    
    // Set JSON header
    header('Content-Type: application/json');
    
    $student_id = intval($_POST['student_id']);
    $fingerprint_template = $_POST['fingerprint_template'] ?? '';
    
    if (empty($fingerprint_template)) {
        ob_clean();
        $response = ['success' => false, 'error' => 'No fingerprint template provided'];
        echo json_encode($response);
        exit();
    }
    
    // Parse the fingerprint data (should be JSON with index_finger and middle_finger arrays)
    $fingerprint_data = json_decode($fingerprint_template, true);
    
    if (!$fingerprint_data || !isset($fingerprint_data['index_finger']) || !isset($fingerprint_data['middle_finger'])) {
        ob_clean();
        $response = ['success' => false, 'error' => 'Invalid fingerprint data format'];
        echo json_encode($response);
        exit();
    }
    
    // Extract fingerprint samples
    $index_finger_samples = $fingerprint_data['index_finger'] ?? [];
    $middle_finger_samples = $fingerprint_data['middle_finger'] ?? [];
    
    // Validate we have samples
    if (empty($index_finger_samples) || empty($middle_finger_samples)) {
        ob_clean();
        $response = ['success' => false, 'error' => 'Both index and middle finger samples are required'];
        echo json_encode($response);
        exit();
    }
    
    // Log sample counts for debugging
    $index_count = count($index_finger_samples);
    $middle_count = count($middle_finger_samples);
    error_log("Index finger samples count: " . $index_count);
    error_log("Middle finger samples count: " . $middle_count);
    error_log("First index sample length: " . (isset($index_finger_samples[0]) ? strlen($index_finger_samples[0]) : 0));
    error_log("First middle sample length: " . (isset($middle_finger_samples[0]) ? strlen($middle_finger_samples[0]) : 0));
    
    // The enrollment service may require 4 samples per finger, duplicate if needed
    // Some services work with 2, but if we get empty responses, we might need 4
    if ($index_count < 4) {
        // Duplicate samples to reach 4 (required by some enrollment services)
        while (count($index_finger_samples) < 4 && count($index_finger_samples) > 0) {
            $index_finger_samples[] = $index_finger_samples[0];
        }
        error_log("Duplicated index samples to reach 4 samples");
    }
    
    if ($middle_count < 4) {
        // Duplicate samples to reach 4
        while (count($middle_finger_samples) < 4 && count($middle_finger_samples) > 0) {
            $middle_finger_samples[] = $middle_finger_samples[0];
        }
        error_log("Duplicated middle samples to reach 4 samples");
    }
    
    // Prepare fingerprint array for enrollment
    $pre_reg_fmd_array = [
        "index_finger" => $index_finger_samples,
        "middle_finger" => $middle_finger_samples
    ];
    
    try {
        // Process fingerprint enrollment through the SDK
        $json_response = enroll_fingerprint($pre_reg_fmd_array);
        
        // Log the raw response for debugging
        error_log("Enrollment raw response (full): " . $json_response);
        error_log("Enrollment raw response (first 500 chars): " . substr($json_response, 0, 500));
        
        // Check if response is a string error
        if ($json_response === "enrollment failed" || $json_response === '"enrollment failed"' || trim($json_response) === "enrollment failed") {
            ob_clean();
            $response = ['success' => false, 'error' => 'Fingerprint enrollment processing failed. Please ensure the fingerprint service is running at http://localhost:9090'];
            echo json_encode($response);
            exit();
        }
        
        // Try to decode as JSON
        $response = json_decode($json_response, true);
        
        // If decoding failed, try as object
        if ($response === null && json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON decode error: " . json_last_error_msg());
            // Try as object
            $response = json_decode($json_response);
            if ($response === null) {
                ob_clean();
                $response = ['success' => false, 'error' => 'Fingerprint enrollment processing failed. Invalid JSON response from service: ' . json_last_error_msg() . '. Response: ' . substr($json_response, 0, 200)];
                echo json_encode($response);
                exit();
            }
        }
        
        // Check if response is empty or invalid
        if (empty($response)) {
            ob_clean();
            $response = ['success' => false, 'error' => 'Fingerprint enrollment processing failed. Empty response from service. Raw: ' . substr($json_response, 0, 200)];
            echo json_encode($response);
            exit();
        }
        
        // Log the decoded response structure
        error_log("Decoded response type: " . gettype($response));
        if (is_array($response)) {
            error_log("Response keys: " . implode(', ', array_keys($response)));
            foreach ($response as $key => $value) {
                if (is_string($value)) {
                    error_log("Response[$key] length: " . strlen($value));
                } else {
                    error_log("Response[$key] type: " . gettype($value));
                }
            }
        }
        
        // Extract the processed template strings (handle both array and object formats)
        if (is_array($response)) {
            $enrolled_index_finger = $response['enrolled_index_finger'] ?? $response['index_finger'] ?? null;
            $enrolled_middle_finger = $response['enrolled_middle_finger'] ?? $response['middle_finger'] ?? null;
        } else {
            $enrolled_index_finger = $response->enrolled_index_finger ?? $response->index_finger ?? null;
            $enrolled_middle_finger = $response->enrolled_middle_finger ?? $response->middle_finger ?? null;
        }
        
        // Log extracted values for debugging
        $index_len = $enrolled_index_finger ? strlen($enrolled_index_finger) : 0;
        $middle_len = $enrolled_middle_finger ? strlen($enrolled_middle_finger) : 0;
        error_log("Extracted index_finger length: " . $index_len);
        error_log("Extracted middle_finger length: " . $middle_len);
        error_log("Index finger value (first 50 chars): " . substr($enrolled_index_finger ?? '', 0, 50));
        error_log("Middle finger value (first 50 chars): " . substr($enrolled_middle_finger ?? '', 0, 50));
        
        // Check if values are empty or null
        if (empty($enrolled_index_finger) || empty($enrolled_middle_finger)) {
            ob_clean();
            $debug_info = "Response type: " . gettype($response) . ", Keys: " . (is_array($response) ? implode(', ', array_keys($response)) : 'N/A');
            $debug_info .= ", Index length: " . $index_len . ", Middle length: " . $middle_len;
            $debug_info .= ", Index is null: " . ($enrolled_index_finger === null ? 'yes' : 'no');
            $debug_info .= ", Middle is null: " . ($enrolled_middle_finger === null ? 'yes' : 'no');
            $response = ['success' => false, 'error' => 'Failed to process fingerprint templates. The enrollment service returned empty values. ' . $debug_info];
            echo json_encode($response);
            exit();
        }
        
        // Store in database using setFingerprintTemplate (handles both insert and update)
        $result = \fingerprint\setFingerprintTemplate(
            $student_id,
            'student',
            $enrolled_index_finger,
            $enrolled_middle_finger
        );
        
        if ($result === "success") {
            // Try to log the enrollment (silently fail if table doesn't exist)
            try {
                \fingerprint\logBiometricAction($student_id, 'student', 'Enrollment', true, null);
            } catch (\Exception $e) {
                // Silently fail - don't break enrollment if logging fails
            }
            
            // Clean buffer and send JSON
            ob_clean();
            $response = ['success' => true, 'message' => 'Fingerprint enrolled successfully'];
            echo json_encode($response);
            exit();
        } else {
            ob_clean();
            $response = ['success' => false, 'error' => 'Failed to save fingerprint to database'];
            echo json_encode($response);
            exit();
        }
        
    } catch (\Exception $e) {
        ob_clean();
        $response = ['success' => false, 'error' => 'Server error: ' . $e->getMessage()];
        echo json_encode($response);
        exit();
    }
}

// Continue with normal page rendering if not AJAX request
// Session already started in dashboard.php, so we don't need to start it again
// Check if session is already active to avoid warnings
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include "../db_conn.php";

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Handle student selection
$selected_student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
$enrollment_success = false;
$enrollment_error = '';

// Check existing fingerprint
if ($selected_student_id > 0) {
    $check_sql = "SELECT ft.*, s.student_id, CONCAT(s.first_name, ' ', s.last_name) as student_name 
                  FROM fingerprint_templates ft 
                  JOIN students s ON ft.user_id = s.id 
                  WHERE ft.user_id = ? AND ft.user_type = 'student'";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "i", $selected_student_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    $existing_fingerprint = mysqli_fetch_assoc($check_result);
    mysqli_stmt_close($check_stmt);
}

// Get all students
$students_sql = "SELECT id, student_id, CONCAT(first_name, ' ', last_name) as name 
                 FROM students 
                 WHERE status = 'Active' 
                 ORDER BY last_name, first_name";
$students_result = mysqli_query($conn, $students_sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Enroll Student Fingerprint</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- DigitalPersona WebSDK Scripts -->
    <script src="../js/es6-shim.js"></script>
    <script src="../js/websdk.client.bundle.min.js"></script>
    <script src="../js/fingerprint.sdk.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <script src="../js/custom.js"></script>
    
    <style>
        body { background: #f5f7fa; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .card { background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); padding: 30px; margin-bottom: 20px; }
        .scanner-area { 
            border: 3px dashed #ddd; 
            border-radius: 12px; 
            padding: 40px; 
            text-align: center; 
            background: #f8f9fa;
            transition: all 0.3s;
        }
        .scanner-area.scanning { 
            border-color: #0069d9; 
            background: #e3f2fd; 
        }
        .scanner-area.success { 
            border-color: #28a745; 
            background: #d4edda; 
        }
        .scanner-area.error { 
            border-color: #dc3545; 
            background: #f8d7da; 
        }
        .fp-image { 
            max-width: 200px; 
            max-height: 200px; 
            border-radius: 8px; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.2); 
        }
        .btn { padding: 12px 24px; border-radius: 6px; font-weight: 600; border: none; cursor: pointer; }
        .btn-primary { background: #0069d9; color: #fff; }
        .btn-success { background: #28a745; color: #fff; }
        .btn-secondary { background: #6c757d; color: #fff; }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .status-badge { 
            display: inline-block; 
            padding: 8px 16px; 
            border-radius: 20px; 
            font-weight: 600; 
            font-size: 14px; 
        }
        .status-ready { background: #fff3cd; color: #856404; }
        .status-scanning { background: #d1ecf1; color: #0c5460; }
        .status-captured { background: #d4edda; color: #155724; }
        .status-error { background: #f8d7da; color: #721c24; }
        .progress { height: 30px; border-radius: 15px; overflow: hidden; }
        .progress-bar { transition: width 0.3s; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h2><i class="fas fa-fingerprint"></i> Student Fingerprint Enrollment</h2>
            <p style="color: #666;">Enroll student fingerprints using DigitalPersona 4500 reader</p>
            
            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px; margin-top: 30px;">
                <!-- Student Selection -->
                <div>
                    <h4><i class="fas fa-user-graduate"></i> Select Student</h4>
                    <form method="GET">
                        <input type="hidden" name="page" value="enroll_student_fingerprint">
                        <select name="student_id" class="form-control" onchange="this.form.submit()">
                            <option value="">-- Select Student --</option>
                            <?php while ($student = mysqli_fetch_assoc($students_result)): ?>
                                <option value="<?php echo $student['id']; ?>" 
                                        <?php echo ($selected_student_id == $student['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($student['student_id'] . ' - ' . $student['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </form>
                    
                    <?php if ($selected_student_id > 0 && isset($existing_fingerprint)): ?>
                        <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-radius: 6px;">
                            <strong>⚠️ Already Enrolled</strong><br>
                            <small>Last updated: <?php echo date('M j, Y', strtotime($existing_fingerprint['updated_at'])); ?></small>
                        </div>
                    <?php endif; ?>
                    
                    <div id="deviceStatus" style="margin-top: 20px; padding: 15px; background: #e9ecef; border-radius: 6px;">
                        <strong>Device Status:</strong><br>
                        <span id="deviceStatusText">Initializing...</span>
                    </div>
                </div>
                
                <!-- Enrollment Area -->
                <div>
                    <?php if ($selected_student_id > 0): 
                        $student_sql = "SELECT student_id, CONCAT(first_name, ' ', last_name) as name FROM students WHERE id = ?";
                        $student_stmt = mysqli_prepare($conn, $student_sql);
                        mysqli_stmt_bind_param($student_stmt, "i", $selected_student_id);
                        mysqli_stmt_execute($student_stmt);
                        $student_result = mysqli_stmt_get_result($student_stmt);
                        $student = mysqli_fetch_assoc($student_result);
                        mysqli_stmt_close($student_stmt);
                    ?>
                        <h4><i class="fas fa-fingerprint"></i> Fingerprint Scanner</h4>
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                            <strong>Enrolling:</strong> <?php echo htmlspecialchars($student['student_id'] . ' - ' . $student['name']); ?>
                        </div>
                        
                        <!-- Reader Selection -->
                        <div style="margin-bottom: 20px;">
                            <label for="enrollReaderSelect" style="display: block; margin-bottom: 5px; font-weight: 600;">
                                <i class="fas fa-scanner"></i> Select Fingerprint Reader
                            </label>
                            <select id="enrollReaderSelect" class="form-control">
                                <option>Select Fingerprint Reader</option>
                            </select>
                        </div>
                        
                        <!-- Fingerprint enrollment icons -->
                        <div style="text-align: center; margin: 20px 0;">
                            <div id="indexFingers" style="display: inline-block; margin: 0 20px;">
                                <div id="index1" style="display: inline-block; margin: 0 5px;">
                                    <span class="myicon icon-indexfinger-not-enrolled" title="not_enrolled"></span>
                                </div>
                                <div id="index2" style="display: inline-block; margin: 0 5px;">
                                    <span class="myicon icon-indexfinger-not-enrolled" title="not_enrolled"></span>
                                </div>
                            </div>
                            <div id="middleFingers" style="display: inline-block; margin: 0 20px;">
                                <div id="middle1" style="display: inline-block; margin: 0 5px;">
                                    <span class="myicon icon-middlefinger-not-enrolled" title="not_enrolled"></span>
                                </div>
                                <div id="middle2" style="display: inline-block; margin: 0 5px;">
                                    <span class="myicon icon-middlefinger-not-enrolled" title="not_enrolled"></span>
                                </div>
                            </div>
                        </div>
                        
                        <div id="scannerArea" class="scanner-area">
                            <div id="fpPreview">
                                <i class="fas fa-fingerprint" style="font-size: 80px; color: #adb5bd;"></i>
                                <p style="margin-top: 20px; color: #6c757d;">Ready to scan</p>
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                            <button type="button" onclick="beginCapture()" class="btn btn-primary">
                                <i class="fas fa-fingerprint"></i> Capture Fingerprint
                            </button>
                            <button type="button" onclick="serverEnroll()" class="btn btn-success">
                                <i class="fas fa-save"></i> Enroll Fingerprint
                            </button>
                            <button type="button" onclick="clearCapture()" class="btn btn-secondary">
                                <i class="fas fa-redo"></i> Clear
                            </button>
                        </div>
                        
                        <!-- Hidden field for student ID -->
                        <input type="hidden" id="userID" value="<?php echo $selected_student_id; ?>">
                        
                        <div id="messageArea" style="margin-top: 20px;"></div>
                        
                    <?php else: ?>
                        <div style="padding: 60px; text-align: center; color: #6c757d;">
                            <i class="fas fa-hand-point-left" style="font-size: 60px; margin-bottom: 20px;"></i>
                            <p>Please select a student to begin enrollment</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="card">
            <h4><i class="fas fa-info-circle"></i> Enrollment Instructions</h4>
            <ol style="line-height: 2;">
                <li>Select a student from the dropdown</li>
                <li>Select a fingerprint reader from the device dropdown (auto-detected)</li>
                <li>Click "Capture Fingerprint" to begin capturing fingerprints</li>
                <li>Place index finger on the reader (2 scans)</li>
                <li>Place middle finger on the reader (2 scans)</li>
                <li>Click "Enroll Fingerprint" to save the template</li>
            </ol>
        </div>
    </div>
    
    <style>
        /* Fingerprint icon styles */
        .myicon {
            font-size: 60px;
            display: inline-block;
        }
        .icon-indexfinger-not-enrolled:before,
        .icon-middlefinger-not-enrolled:before {
            content: "👆";
            opacity: 0.3;
            filter: grayscale(100%);
        }
        .icon-indexfinger-enrolled:before,
        .icon-middlefinger-enrolled:before {
            content: "👆";
            color: #28a745;
        }
        .capture-indexfinger:before,
        .capture-middlefinger:before {
            content: "👆";
            color: #007bff;
            animation: pulse 1s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>
    
    <script>
    // Initialize fingerprint reader on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Check if Fingerprint SDK is loaded
        if (typeof Fingerprint === 'undefined') {
            console.error('Fingerprint SDK not loaded');
            var statusText = document.getElementById('deviceStatusText');
            if (statusText) {
                statusText.innerHTML = '<span style="color: #dc3545;">✗ Error: Fingerprint SDK not loaded. Please contact administrator.</span>';
            }
            return;
        }
        
        // Check if a student is selected
        var userID = document.getElementById('userID');
        if (userID && userID.value) {
            // Initialize fingerprint enrollment
            try {
                beginEnrollment();
                console.log('Fingerprint enrollment initialized');
                
                // Update status
                var statusText = document.getElementById('deviceStatusText');
                if (statusText) {
                    statusText.innerHTML = '<span style="color: #28a745;">✓ Device detection initialized. Please select a fingerprint reader.</span>';
                }
            } catch(e) {
                console.error('Failed to initialize fingerprint reader:', e);
                var statusText = document.getElementById('deviceStatusText');
                if (statusText) {
                    statusText.innerHTML = '<span style="color: #dc3545;">✗ Error: ' + e.message + '</span>';
                }
            }
        } else {
            // Even without student selected, try to detect devices for preview
            try {
                beginEnrollment();
                console.log('Device detection ready');
            } catch(e) {
                console.warn('Device detection unavailable:', e);
            }
        }
    });
    
    // Override serverEnroll to use student enrollment endpoint
    var originalServerEnroll = serverEnroll;
    serverEnroll = function() {
        if (!readyForEnroll()) {
            toastr.error('Please select a student and fingerprint reader first');
            return;
        }
        
        // Check if currentHand exists and has captured samples
        if (!myReader.currentHand) {
            toastr.error('Please capture fingerprints first');
            return;
        }

        if (myReader.currentHand.index_finger.length === 0 && myReader.currentHand.middle_finger.length === 0) {
            toastr.error('Please capture at least one fingerprint sample');
            return;
        }
        
        let studentIdEl = document.getElementById('userID');
        if (!studentIdEl || !studentIdEl.value) {
            toastr.error('Student ID not found');
            return;
        }
        
        let studentId = studentIdEl.value;
        let successMessage = "Enrollment Successful!";
        let failedMessage = "Enrollment Failed!";
        
        // Create the fingerprint template JSON
        let fingerprintData = {
            index_finger: myReader.currentHand.index_finger,
            middle_finger: myReader.currentHand.middle_finger
        };
        let fingerprintTemplate = JSON.stringify(fingerprintData);
        
        let formData = new FormData();
        formData.append('ajax_enroll', '1');
        formData.append('student_id', studentId);
        formData.append('fingerprint_template', fingerprintTemplate);
        
        let xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function () {
            if (this.readyState === 4 && this.status === 200) {
                try {
                    // Clean response text (remove any whitespace or extra output)
                    let responseText = this.responseText.trim();
                    let result = JSON.parse(responseText);
                    
                    // Check success explicitly
                    if (result.success === true) {
                        toastr.success(result.message || successMessage);
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    } else {
                        toastr.error(result.error || failedMessage);
                    }
                } catch(e) {
                    console.error('JSON Parse Error:', e);
                    console.error('Response Text:', this.responseText);
                    console.error('Response Length:', this.responseText.length);
                    
                    // Check if response is empty
                    if (!this.responseText || this.responseText.trim() === '') {
                        toastr.error(failedMessage + ': Empty response from server. Please check server logs.');
                    } else {
                        toastr.error(failedMessage + ': ' + this.responseText.substring(0, 100));
                    }
                }
            } else if (this.readyState === 4 && this.status !== 200) {
                console.error('HTTP Error:', this.status, this.statusText);
                toastr.error(failedMessage + ': HTTP ' + this.status + ' - ' + this.statusText);
            }
        };
        xhttp.open("POST", window.location.href, true);
        xhttp.send(formData);
    };
    </script>
</body>
</html>