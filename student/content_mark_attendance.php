<?php
// Student Attendance Marking - Multiple Methods
$success_message = '';
$error_message = '';

// Handle attendance marking
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_attendance'])) {
    $method = mysqli_real_escape_string($conn, $_POST['method']);
    $session_id = intval($_POST['session_id']);
    
    $verified = false;
    
    // Verify based on method
    if ($method == 'PIN') {
        $entered_pin = mysqli_real_escape_string($conn, $_POST['pin']);
        
        // Get user's PIN
        $pin_sql = "SELECT attendance_pin FROM users WHERE id = ?";
        $pin_stmt = mysqli_prepare($conn, $pin_sql);
        mysqli_stmt_bind_param($pin_stmt, "i", $student_id);
        mysqli_stmt_execute($pin_stmt);
        $pin_result = mysqli_stmt_get_result($pin_stmt);
        $pin_row = mysqli_fetch_assoc($pin_result);
        
        if ($pin_row && $pin_row['attendance_pin'] == $entered_pin) {
            $verified = true;
        } else {
            $error_message = "Invalid PIN! Please try again.";
        }
        mysqli_stmt_close($pin_stmt);
        
    } elseif ($method == 'QR Code') {
        $qr_data = mysqli_real_escape_string($conn, $_POST['qr_data']);
        
        // Verify QR code (in production, scan actual QR code)
        if (!empty($qr_data)) {
            $verified = true;
        } else {
            $error_message = "QR Code verification failed!";
        }
        
    } elseif ($method == 'Manual') {
        // Manual entry - always verified (teacher will approve later)
        $verified = true;
    }
    
    if ($verified) {
        $current_datetime = date('Y-m-d H:i:s');
        $current_date = date('Y-m-d');
        
        // Check if already marked
        $check_sql = "SELECT id FROM user_attendance WHERE user_id = ? AND session_id = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "ii", $student_id, $session_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        
        if (mysqli_num_rows($check_result) == 0) {
            // Determine status based on time
            $session_sql = "SELECT start_time FROM attendance_sessions WHERE id = ?";
            $session_stmt = mysqli_prepare($conn, $session_sql);
            mysqli_stmt_bind_param($session_stmt, "i", $session_id);
            mysqli_stmt_execute($session_stmt);
            $session_result = mysqli_stmt_get_result($session_stmt);
            $session_row = mysqli_fetch_assoc($session_result);
            
            $status = 'Present';
            if ($session_row) {
                $start_time = strtotime($session_row['start_time']);
                $current_time = strtotime(date('H:i:s'));
                if ($current_time > $start_time + 900) { // 15 minutes late
                    $status = 'Late';
                }
            }
            
            // Insert attendance
            $insert_sql = "INSERT INTO user_attendance (user_id, session_id, attendance_date, check_in_time, status, attendance_method, is_verified) VALUES (?, ?, ?, ?, ?, ?, 1)";
            $insert_stmt = mysqli_prepare($conn, $insert_sql);
            mysqli_stmt_bind_param($insert_stmt, "iissss", $student_id, $session_id, $current_date, $current_datetime, $status, $method);
            
            if (mysqli_stmt_execute($insert_stmt)) {
                $success_message = "Attendance marked successfully! Status: $status";
            } else {
                $error_message = "Error marking attendance: " . mysqli_error($conn);
            }
            mysqli_stmt_close($insert_stmt);
            mysqli_stmt_close($session_stmt);
        } else {
            $error_message = "You have already marked attendance for this session!";
        }
        mysqli_stmt_close($check_stmt);
    }
}

// Get active sessions for today
$today = date('Y-m-d');
$sessions_sql = "SELECT ats.*, u.name as teacher_name 
                 FROM attendance_sessions ats
                 JOIN users u ON ats.teacher_id = u.id
                 WHERE ats.session_date = ? 
                 AND ats.status IN ('Scheduled', 'Active')
                 AND (ats.department = (SELECT department FROM users WHERE id = ?) 
                      OR ats.department IS NULL)
                 ORDER BY ats.start_time";
$sessions_stmt = mysqli_prepare($conn, $sessions_sql);
mysqli_stmt_bind_param($sessions_stmt, "si", $today, $student_id);
mysqli_stmt_execute($sessions_stmt);
$sessions_result = mysqli_stmt_get_result($sessions_stmt);
?>

<style>
    .attendance-card {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        padding: 30px;
        margin-bottom: 20px;
    }
    .method-selector {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin: 30px 0;
    }
    .method-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        border-radius: 8px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
        border: 3px solid transparent;
    }
    .method-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 16px rgba(102, 126, 234, 0.4);
    }
    .method-card.selected {
        border-color: #27ae60;
        box-shadow: 0 0 20px rgba(39, 174, 96, 0.5);
    }
    .method-card i {
        font-size: 3em;
        margin-bottom: 15px;
    }
    .session-card {
        background: #f8f9fa;
        border-left: 4px solid #3498db;
        padding: 20px;
        margin-bottom: 15px;
        border-radius: 4px;
        transition: all 0.3s;
    }
    .session-card:hover {
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .session-card.selected {
        background: #e3f2fd;
        border-left-color: #27ae60;
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
    .form-control {
        width: 100%;
        padding: 10px 15px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
        margin-top: 10px;
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
    .pin-input {
        font-size: 2em;
        text-align: center;
        letter-spacing: 10px;
        max-width: 300px;
        margin: 20px auto;
    }
    .qr-scanner {
        width: 300px;
        height: 300px;
        margin: 20px auto;
        background: #f0f0f0;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 3px dashed #3498db;
    }
</style>

<div class="attendance-card">
    <h3><i class="fas fa-check-circle"></i> Mark Your Attendance</h3>
    <p>Select a session and choose your preferred attendance method</p>

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

    <h4 style="margin-top:30px;">Today's Sessions</h4>
    
    <?php if (mysqli_num_rows($sessions_result) > 0): ?>
        <form method="POST" action="" id="attendanceForm">
            <div id="sessionsList">
                <?php while ($session = mysqli_fetch_assoc($sessions_result)): ?>
                    <div class="session-card" onclick="selectSession(<?php echo $session['id']; ?>, this)">
                        <input type="radio" name="session_id" value="<?php echo $session['id']; ?>" style="display:none;">
                        <div style="display:flex;justify-content:space-between;align-items:start;">
                            <div>
                                <h5 style="margin:0;color:#2c3e50;"><?php echo htmlspecialchars($session['session_name']); ?></h5>
                                <p style="margin:5px 0;color:#7f8c8d;">
                                    <i class="fas fa-book"></i> <?php echo htmlspecialchars($session['subject']); ?> |
                                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($session['teacher_name']); ?>
                                </p>
                                <p style="margin:5px 0;color:#7f8c8d;">
                                    <i class="fas fa-clock"></i> <?php echo date('g:i A', strtotime($session['start_time'])); ?> - 
                                    <?php echo date('g:i A', strtotime($session['end_time'])); ?>
                                </p>
                            </div>
                            <span class="badge" style="background:#3498db;color:white;padding:5px 15px;border-radius:12px;">
                                <?php echo $session['status']; ?>
                            </span>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <h4 style="margin-top:30px;">Select Attendance Method</h4>
            <div class="method-selector">
                <div class="method-card" onclick="selectMethod('PIN', this)">
                    <i class="fas fa-keyboard"></i>
                    <h5>PIN Entry</h5>
                    <p>Enter your 6-digit PIN</p>
                </div>
                <div class="method-card" onclick="selectMethod('QR Code', this)">
                    <i class="fas fa-qrcode"></i>
                    <h5>QR Code</h5>
                    <p>Scan your QR code</p>
                </div>
                <div class="method-card" onclick="selectMethod('Manual', this)">
                    <i class="fas fa-hand-pointer"></i>
                    <h5>Manual Entry</h5>
                    <p>Quick manual mark</p>
                </div>
            </div>

            <input type="hidden" name="method" id="selectedMethod">

            <div id="pinSection" style="display:none;text-align:center;">
                <input type="text" class="form-control pin-input" name="pin" id="pinInput" maxlength="6" placeholder="••••••">
            </div>

            <div id="qrSection" style="display:none;text-align:center;">
                <div class="qr-scanner">
                    <div>
                        <i class="fas fa-qrcode" style="font-size:4em;color:#3498db;"></i>
                        <p>Scan QR Code Here</p>
                    </div>
                </div>
                <input type="hidden" name="qr_data" id="qrData">
                <button type="button" class="btn btn-primary" onclick="simulateQRScan()">Simulate QR Scan</button>
            </div>

            <div id="manualSection" style="display:none;text-align:center;">
                <p><i class="fas fa-info-circle"></i> Manual attendance will be verified by your teacher</p>
            </div>

            <div style="text-align:center;margin-top:30px;">
                <button type="submit" name="mark_attendance" class="btn btn-success" id="submitBtn" disabled>
                    <i class="fas fa-check"></i> Mark Attendance
                </button>
            </div>
        </form>
    <?php else: ?>
        <div style="text-align:center;padding:40px;color:#7f8c8d;">
            <i class="fas fa-calendar-times" style="font-size:3em;margin-bottom:15px;"></i>
            <p>No active sessions available for today.</p>
        </div>
    <?php endif; ?>
</div>

<script>
let selectedSession = null;
let selectedMethod = null;

function selectSession(sessionId, element) {
    document.querySelectorAll('.session-card').forEach(card => card.classList.remove('selected'));
    element.classList.add('selected');
    element.querySelector('input[type="radio"]').checked = true;
    selectedSession = sessionId;
    checkFormReady();
}

function selectMethod(method, element) {
    document.querySelectorAll('.method-card').forEach(card => card.classList.remove('selected'));
    element.classList.add('selected');
    document.getElementById('selectedMethod').value = method;
    selectedMethod = method;
    
    // Hide all sections
    document.getElementById('pinSection').style.display = 'none';
    document.getElementById('qrSection').style.display = 'none';
    document.getElementById('manualSection').style.display = 'none';
    
    // Show relevant section
    if (method === 'PIN') {
        document.getElementById('pinSection').style.display = 'block';
        document.getElementById('pinInput').focus();
    } else if (method === 'QR Code') {
        document.getElementById('qrSection').style.display = 'block';
    } else if (method === 'Manual') {
        document.getElementById('manualSection').style.display = 'block';
    }
    
    checkFormReady();
}

function checkFormReady() {
    const submitBtn = document.getElementById('submitBtn');
    if (selectedSession && selectedMethod) {
        submitBtn.disabled = false;
    } else {
        submitBtn.disabled = true;
    }
}

function simulateQRScan() {
    // Simulate QR code scan
    const qrData = 'QR_' + Math.random().toString(36).substring(2, 15);
    document.getElementById('qrData').value = qrData;
    alert('QR Code scanned successfully!');
}

// Auto-format PIN input
document.getElementById('pinInput')?.addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9]/g, '');
});
</script>
