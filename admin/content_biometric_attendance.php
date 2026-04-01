<?php
// Biometric Attendance Logging System
$success_message = '';
$error_message = '';

// Handle attendance logging
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['log_attendance'])) {
    $user_type = mysqli_real_escape_string($conn, $_POST['user_type']);
    $user_id = intval($_POST['user_id']);
    $action_type = mysqli_real_escape_string($conn, $_POST['action_type']);
    $fingerprint_data = mysqli_real_escape_string($conn, $_POST['fingerprint_data'] ?? '');
    
    // Verify fingerprint (simulated - in production, use actual biometric SDK)
    $is_verified = false;
    $match_score = 0;
    
    if (!empty($fingerprint_data)) {
        // Check if fingerprint exists in database
        $fp_check_sql = "SELECT id, fingerprint_template FROM fingerprint_templates WHERE user_id = ? AND user_type = ?";
        $fp_stmt = mysqli_prepare($conn, $fp_check_sql);
        mysqli_stmt_bind_param($fp_stmt, "is", $user_id, $user_type);
        mysqli_stmt_execute($fp_stmt);
        $fp_result = mysqli_stmt_get_result($fp_stmt);
        
        if (mysqli_num_rows($fp_result) > 0) {
            $fp_row = mysqli_fetch_assoc($fp_result);
            // Simulate fingerprint matching (in production, use biometric matching algorithm)
            $match_score = rand(85, 99) + (rand(0, 99) / 100); // Simulated score
            $is_verified = $match_score >= 85.00; // 85% threshold
        }
        mysqli_stmt_close($fp_stmt);
    }
    
    if ($is_verified) {
        $current_datetime = date('Y-m-d H:i:s');
        $current_date = date('Y-m-d');
        
        if ($user_type == 'student') {
            // Log student attendance
            if ($action_type == 'Check-In') {
                // Check if already checked in today
                $check_sql = "SELECT id FROM student_attendance WHERE student_id = ? AND attendance_date = ?";
                $check_stmt = mysqli_prepare($conn, $check_sql);
                mysqli_stmt_bind_param($check_stmt, "is", $user_id, $current_date);
                mysqli_stmt_execute($check_stmt);
                $check_result = mysqli_stmt_get_result($check_stmt);
                
                if (mysqli_num_rows($check_result) == 0) {
                    // Determine if late (after 8:00 AM)
                    $check_in_hour = intval(date('H'));
                    $status = ($check_in_hour >= 8) ? 'Late' : 'Present';
                    
                    $insert_sql = "INSERT INTO student_attendance (student_id, attendance_date, check_in_time, status, is_biometric_verified, fingerprint_match_score) VALUES (?, ?, ?, ?, 1, ?)";
                    $insert_stmt = mysqli_prepare($conn, $insert_sql);
                    mysqli_stmt_bind_param($insert_stmt, "isssd", $user_id, $current_date, $current_datetime, $status, $match_score);
                    
                    if (mysqli_stmt_execute($insert_stmt)) {
                        $success_message = "Check-in successful! Status: $status (Match: " . number_format($match_score, 2) . "%)";
                    }
                    mysqli_stmt_close($insert_stmt);
                } else {
                    $error_message = "Already checked in today!";
                }
                mysqli_stmt_close($check_stmt);
                
            } elseif ($action_type == 'Check-Out') {
                // Update check-out time
                $update_sql = "UPDATE student_attendance SET check_out_time = ? WHERE student_id = ? AND attendance_date = ?";
                $update_stmt = mysqli_prepare($conn, $update_sql);
                mysqli_stmt_bind_param($update_stmt, "sis", $current_datetime, $user_id, $current_date);
                
                if (mysqli_stmt_execute($update_stmt) && mysqli_stmt_affected_rows($update_stmt) > 0) {
                    $success_message = "Check-out successful! (Match: " . number_format($match_score, 2) . "%)";
                } else {
                    $error_message = "No check-in record found for today!";
                }
                mysqli_stmt_close($update_stmt);
            }
        }
        
        // Log biometric authentication
        $log_sql = "INSERT INTO biometric_logs (user_id, user_type, action_type, fingerprint_match_score, success, ip_address, timestamp) VALUES (?, ?, ?, ?, 1, ?, ?)";
        $log_stmt = mysqli_prepare($conn, $log_sql);
        $ip_address = $_SERVER['REMOTE_ADDR'];
        mysqli_stmt_bind_param($log_stmt, "issdss", $user_id, $user_type, $action_type, $match_score, $ip_address, $current_datetime);
        mysqli_stmt_execute($log_stmt);
        mysqli_stmt_close($log_stmt);
        
    } else {
        $error_message = "Fingerprint verification failed! Match score too low.";
        
        // Log failed attempt
        $log_sql = "INSERT INTO biometric_logs (user_id, user_type, action_type, fingerprint_match_score, success, ip_address, timestamp) VALUES (?, ?, 'Verification Failed', ?, 0, ?, ?)";
        $log_stmt = mysqli_prepare($conn, $log_sql);
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $current_datetime = date('Y-m-d H:i:s');
        mysqli_stmt_bind_param($log_stmt, "isdss", $user_id, $user_type, $match_score, $ip_address, $current_datetime);
        mysqli_stmt_execute($log_stmt);
        mysqli_stmt_close($log_stmt);
    }
}

// Get students for dropdown
$students_sql = "SELECT id, student_id, CONCAT(first_name, ' ', last_name) as name FROM students WHERE status = 'Active' ORDER BY first_name";
$students_result = mysqli_query($conn, $students_sql);
?>

<style>
    .biometric-card {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        padding: 30px;
        max-width: 600px;
        margin: 0 auto;
    }
    .fingerprint-scanner {
        width: 200px;
        height: 200px;
        margin: 20px auto;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s;
        position: relative;
        overflow: hidden;
    }
    .fingerprint-scanner:hover {
        transform: scale(1.05);
        box-shadow: 0 8px 16px rgba(102, 126, 234, 0.4);
    }
    .fingerprint-scanner.scanning {
        animation: pulse 1.5s infinite;
    }
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.1); }
    }
    .fingerprint-icon {
        font-size: 5em;
        color: #fff;
    }
    .scan-status {
        text-align: center;
        margin-top: 20px;
        font-size: 1.1em;
        font-weight: 600;
    }
    .alert {
        padding: 12px 20px;
        margin-bottom: 20px;
        border-radius: 4px;
        font-weight: 500;
    }
    .alert-success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    .form-group {
        margin-bottom: 20px;
    }
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #2c3e50;
    }
    .form-control {
        width: 100%;
        padding: 10px 15px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
    }
    .btn {
        padding: 12px 30px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        transition: all 0.3s;
    }
    .btn-primary {
        background: #3498db;
        color: #fff;
    }
    .btn-success {
        background: #27ae60;
        color: #fff;
    }
    .btn-warning {
        background: #f39c12;
        color: #fff;
    }
    .action-buttons {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
        margin-top: 20px;
    }
</style>

<div class="biometric-card">
    <h2 style="text-align:center;color:#2c3e50;"><i class="fas fa-fingerprint"></i> Biometric Attendance System</h2>
    <p style="text-align:center;color:#7f8c8d;">Secure attendance logging with fingerprint authentication</p>

    <?php if ($success_message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="" id="attendanceForm">
        <div class="form-group">
            <label for="user_type">User Type</label>
            <select class="form-control" id="user_type" name="user_type" required>
                <option value="student">Student</option>
                <option value="staff">Staff</option>
                <option value="tutor">Tutor</option>
            </select>
        </div>

        <div class="form-group">
            <label for="user_id">Select Student</label>
            <select class="form-control" id="user_id" name="user_id" required>
                <option value="">-- Select Student --</option>
                <?php while ($student = mysqli_fetch_assoc($students_result)): ?>
                    <option value="<?php echo $student['id']; ?>">
                        <?php echo htmlspecialchars($student['student_id'] . ' - ' . $student['name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="fingerprint-scanner" id="fingerprintScanner" onclick="scanFingerprint()">
            <i class="fas fa-fingerprint fingerprint-icon"></i>
        </div>

        <div class="scan-status" id="scanStatus">Click to scan fingerprint</div>

        <input type="hidden" name="fingerprint_data" id="fingerprintData" value="">
        <input type="hidden" name="action_type" id="actionType" value="">

        <div class="action-buttons">
            <button type="button" class="btn btn-success" onclick="submitAttendance('Check-In')">
                <i class="fas fa-sign-in-alt"></i> Check-In
            </button>
            <button type="button" class="btn btn-warning" onclick="submitAttendance('Check-Out')">
                <i class="fas fa-sign-out-alt"></i> Check-Out
            </button>
        </div>
    </form>
</div>

<script>
let fingerprintScanned = false;

function scanFingerprint() {
    const scanner = document.getElementById('fingerprintScanner');
    const status = document.getElementById('scanStatus');
    
    scanner.classList.add('scanning');
    status.textContent = 'Scanning fingerprint...';
    status.style.color = '#f39c12';
    
    // Simulate fingerprint scanning (in production, integrate with actual biometric device)
    setTimeout(() => {
        // Generate simulated fingerprint data
        const fingerprintData = 'FP_' + Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
        document.getElementById('fingerprintData').value = fingerprintData;
        
        scanner.classList.remove('scanning');
        status.textContent = 'Fingerprint captured successfully!';
        status.style.color = '#27ae60';
        fingerprintScanned = true;
    }, 2000);
}

function submitAttendance(actionType) {
    if (!fingerprintScanned) {
        alert('Please scan fingerprint first!');
        return;
    }
    
    const userId = document.getElementById('user_id').value;
    if (!userId) {
        alert('Please select a user!');
        return;
    }
    
    document.getElementById('actionType').value = actionType;
    document.getElementById('attendanceForm').submit();
}
</script>
