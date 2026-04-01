<?php
/**
 * Insert Attendance Record
 * Handles attendance for students, staff, and tutors with biometric verification
 */

session_start();
include "../db_conn.php";

header('Content-Type: application/json');

// Initialize response
$response = [
    'success' => false,
    'type' => 'error',
    'response' => 'Invalid request',
    'studentID' => '',
    'fullname' => '',
    'year' => '',
    'course' => '',
    'img' => 'default.png'
];

try {
    // Get POST data
    $user_id = isset($_POST['userId']) ? intval($_POST['userId']) : 0;
    $user_type = isset($_POST['userType']) ? $_POST['userType'] : 'student';
    $type = isset($_POST['type']) ? $_POST['type'] : 'IN'; // IN or OUT
    $match_score = isset($_POST['matchScore']) ? floatval($_POST['matchScore']) : 95.0;
    $student_id_string = isset($_POST['studentId']) ? $_POST['studentId'] : null;
    
    // If user_id is 0 or invalid, try to look up by student_id (for students)
    if (($user_id <= 0 || $user_id == 0) && $user_type === 'student' && $student_id_string) {
        // Look up the numeric ID from students table using student_id (student number)
        $lookup_sql = "SELECT id FROM students WHERE student_id = ? LIMIT 1";
        $lookup_stmt = mysqli_prepare($conn, $lookup_sql);
        mysqli_stmt_bind_param($lookup_stmt, "s", $student_id_string);
        mysqli_stmt_execute($lookup_stmt);
        $lookup_result = mysqli_stmt_get_result($lookup_stmt);
        $lookup_row = mysqli_fetch_assoc($lookup_result);
        mysqli_stmt_close($lookup_stmt);
        
        if ($lookup_row && isset($lookup_row['id'])) {
            $user_id = intval($lookup_row['id']);
        }
    }
    
    // For backward compatibility with student attendance (if studentId is numeric)
    if (isset($_POST['studentId']) && is_numeric($_POST['studentId'])) {
        $user_id = intval($_POST['studentId']);
        $user_type = 'student';
    }
    
    // Validate user ID
    if ($user_id <= 0) {
        $response['response'] = 'Invalid user ID. Please ensure the fingerprint is properly enrolled.';
        echo json_encode($response);
        exit();
    }
    
    // Get current date and time
    $today = date('Y-m-d');
    $current_time = date('H:i:s');
    $current_datetime = date('Y-m-d H:i:s');
    
    // Process based on user type
    switch ($user_type) {
        case 'student':
            $result = insertStudentAttendance($conn, $user_id, $today, $current_datetime, $type, $match_score);
            break;
            
        case 'staff':
            $result = insertStaffAttendance($conn, $user_id, $today, $current_time, $type);
            break;
            
        case 'tutor':
            $result = insertTutorAttendance($conn, $user_id, $today, $current_datetime, $type, $match_score);
            break;
            
        default:
            $response['response'] = 'Invalid user type';
            echo json_encode($response);
            exit();
    }
    
    // Return the result
    echo json_encode($result);
    
} catch (Exception $e) {
    $response['response'] = 'Error: ' . $e->getMessage();
    echo json_encode($response);
}

/**
 * Insert student attendance
 */
function insertStudentAttendance($conn, $student_id, $date, $datetime, $type, $match_score) {
    // Get student info
    $sql = "SELECT s.*, y.year_level_name 
            FROM students s 
            LEFT JOIN year_levels y ON s.year_level_id = y.id 
            WHERE s.id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $student_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $student = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$student) {
        return [
            'success' => false,
            'type' => 'error',
            'response' => 'Student not found',
            'studentID' => '',
            'fullname' => '',
            'year' => '',
            'course' => '',
            'img' => 'default.png'
        ];
    }
    
    // Check if attendance record exists for today
    $check_sql = "SELECT * FROM student_attendance WHERE student_id = ? AND attendance_date = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "is", $student_id, $date);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    $existing = mysqli_fetch_assoc($check_result);
    mysqli_stmt_close($check_stmt);
    
    $message = '';
    
    if ($existing) {
        // Update existing record
        if ($type == 'IN') {
            // Already checked in
            if ($existing['check_in_time']) {
                $message = 'Already checked in at ' . date('h:i A', strtotime($existing['check_in_time']));
                $success = false;
                $msg_type = 'warning';
            } else {
                // Update check-in time
                $update_sql = "UPDATE student_attendance 
                              SET check_in_time = ?, status = 'Present', 
                                  is_biometric_verified = 1, fingerprint_match_score = ? 
                              WHERE student_id = ? AND attendance_date = ?";
                $update_stmt = mysqli_prepare($conn, $update_sql);
                mysqli_stmt_bind_param($update_stmt, "sdis", $datetime, $match_score, $student_id, $date);
                $success = mysqli_stmt_execute($update_stmt);
                mysqli_stmt_close($update_stmt);
                
                $message = 'Check-in successful at ' . date('h:i A');
                $msg_type = 'success';
            }
        } else { // OUT
            // Check if already checked in
            if (!$existing['check_in_time']) {
                $message = 'Please check in first';
                $success = false;
                $msg_type = 'warning';
            } elseif ($existing['check_out_time']) {
                $message = 'Already checked out at ' . date('h:i A', strtotime($existing['check_out_time']));
                $success = false;
                $msg_type = 'warning';
            } else {
                // Update check-out time
                $update_sql = "UPDATE student_attendance 
                              SET check_out_time = ? 
                              WHERE student_id = ? AND attendance_date = ?";
                $update_stmt = mysqli_prepare($conn, $update_sql);
                mysqli_stmt_bind_param($update_stmt, "sis", $datetime, $student_id, $date);
                $success = mysqli_stmt_execute($update_stmt);
                mysqli_stmt_close($update_stmt);
                
                $message = 'Check-out successful at ' . date('h:i A');
                $msg_type = 'success';
            }
        }
    } else {
        // Insert new record
        if ($type == 'IN') {
            $insert_sql = "INSERT INTO student_attendance 
                          (student_id, attendance_date, check_in_time, status, is_biometric_verified, fingerprint_match_score) 
                          VALUES (?, ?, ?, 'Present', 1, ?)";
            $insert_stmt = mysqli_prepare($conn, $insert_sql);
            mysqli_stmt_bind_param($insert_stmt, "issd", $student_id, $date, $datetime, $match_score);
            $success = mysqli_stmt_execute($insert_stmt);
            mysqli_stmt_close($insert_stmt);
            
            $message = 'Check-in successful at ' . date('h:i A');
            $msg_type = 'success';
        } else {
            // Can't check out without checking in first
            $message = 'Please check in first';
            $success = false;
            $msg_type = 'warning';
        }
    }
    
    // Log to biometric_logs
    if ($success) {
        $log_sql = "INSERT INTO biometric_logs 
                   (user_id, user_type, action_type, fingerprint_match_score, success, ip_address, device_info) 
                   VALUES (?, 'student', ?, ?, 1, ?, ?)";
        $log_stmt = mysqli_prepare($conn, $log_sql);
        $action = $type == 'IN' ? 'Check-In' : 'Check-Out';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $device = $_SERVER['HTTP_USER_AGENT'] ?? '';
        mysqli_stmt_bind_param($log_stmt, "isdss", $student_id, $action, $match_score, $ip, $device);
        mysqli_stmt_execute($log_stmt);
        mysqli_stmt_close($log_stmt);
    }
    
    // Prepare response
    return [
        'success' => $success,
        'type' => $msg_type,
        'response' => $message,
        'studentID' => $student['student_id'],
        'fullname' => $student['first_name'] . ' ' . $student['last_name'],
        'year' => $student['year_level_name'] ?? 'N/A',
        'course' => $student['section'] ?? 'N/A',
        'img' => $student['profile_picture'] ?? 'default.png',
        'match_score' => round($match_score, 2)
    ];
}

/**
 * Insert staff attendance
 */
function insertStaffAttendance($conn, $staff_id, $date, $time, $type) {
    // Get staff info
    $sql = "SELECT * FROM users WHERE id = ? AND role = 'user'";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $staff_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $staff = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$staff) {
        return [
            'success' => false,
            'type' => 'error',
            'response' => 'Staff member not found',
            'studentID' => '',
            'fullname' => '',
            'year' => '',
            'course' => '',
            'img' => 'default.png'
        ];
    }
    
    // Check if attendance record exists for today
    $check_sql = "SELECT * FROM staff_attendance WHERE staff_id = ? AND attendance_date = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "is", $staff_id, $date);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    $existing = mysqli_fetch_assoc($check_result);
    mysqli_stmt_close($check_stmt);
    
    $message = '';
    
    if ($existing) {
        // Update existing record
        if ($type == 'IN') {
            if ($existing['check_in_time']) {
                $message = 'Already checked in at ' . date('h:i A', strtotime($existing['check_in_time']));
                $success = false;
                $msg_type = 'warning';
            } else {
                $update_sql = "UPDATE staff_attendance 
                              SET check_in_time = ?, status = 'Present' 
                              WHERE staff_id = ? AND attendance_date = ?";
                $update_stmt = mysqli_prepare($conn, $update_sql);
                mysqli_stmt_bind_param($update_stmt, "sis", $time, $staff_id, $date);
                $success = mysqli_stmt_execute($update_stmt);
                mysqli_stmt_close($update_stmt);
                
                $message = 'Check-in successful at ' . date('h:i A', strtotime($time));
                $msg_type = 'success';
            }
        } else {
            if (!$existing['check_in_time']) {
                $message = 'Please check in first';
                $success = false;
                $msg_type = 'warning';
            } elseif ($existing['check_out_time']) {
                $message = 'Already checked out at ' . date('h:i A', strtotime($existing['check_out_time']));
                $success = false;
                $msg_type = 'warning';
            } else {
                $update_sql = "UPDATE staff_attendance 
                              SET check_out_time = ? 
                              WHERE staff_id = ? AND attendance_date = ?";
                $update_stmt = mysqli_prepare($conn, $update_sql);
                mysqli_stmt_bind_param($update_stmt, "sis", $time, $staff_id, $date);
                $success = mysqli_stmt_execute($update_stmt);
                mysqli_stmt_close($update_stmt);
                
                $message = 'Check-out successful at ' . date('h:i A', strtotime($time));
                $msg_type = 'success';
            }
        }
    } else {
        if ($type == 'IN') {
            $insert_sql = "INSERT INTO staff_attendance 
                          (staff_id, attendance_date, check_in_time, status) 
                          VALUES (?, ?, ?, 'Present')";
            $insert_stmt = mysqli_prepare($conn, $insert_sql);
            mysqli_stmt_bind_param($insert_stmt, "iss", $staff_id, $date, $time);
            $success = mysqli_stmt_execute($insert_stmt);
            mysqli_stmt_close($insert_stmt);
            
            $message = 'Check-in successful at ' . date('h:i A', strtotime($time));
            $msg_type = 'success';
        } else {
            $message = 'Please check in first';
            $success = false;
            $msg_type = 'warning';
        }
    }
    
    // Log to biometric_logs
    if ($success) {
        $log_sql = "INSERT INTO biometric_logs 
                   (user_id, user_type, action_type, success, ip_address, device_info) 
                   VALUES (?, 'staff', ?, 1, ?, ?)";
        $log_stmt = mysqli_prepare($conn, $log_sql);
        $action = $type == 'IN' ? 'Check-In' : 'Check-Out';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $device = $_SERVER['HTTP_USER_AGENT'] ?? '';
        mysqli_stmt_bind_param($log_stmt, "isss", $staff_id, $action, $ip, $device);
        mysqli_stmt_execute($log_stmt);
        mysqli_stmt_close($log_stmt);
    }
    
    return [
        'success' => $success,
        'type' => $msg_type,
        'response' => $message,
        'studentID' => $staff['teacher_id'] ?? $staff['username'],
        'fullname' => $staff['name'],
        'year' => 'Staff',
        'course' => $staff['department'] ?? 'N/A',
        'img' => $staff['profile_picture'] ?? 'default.png'
    ];
}

/**
 * Insert tutor attendance
 */
function insertTutorAttendance($conn, $tutor_id, $date, $datetime, $type, $match_score) {
    // Get tutor info
    $sql = "SELECT * FROM tutors WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $tutor_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $tutor = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$tutor) {
        return [
            'success' => false,
            'type' => 'error',
            'response' => 'Tutor not found',
            'studentID' => '',
            'fullname' => '',
            'year' => '',
            'course' => '',
            'img' => 'default.png'
        ];
    }
    
    // Check if attendance record exists for today
    $check_sql = "SELECT * FROM tutor_attendance WHERE tutor_id = ? AND attendance_date = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "is", $tutor_id, $date);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    $existing = mysqli_fetch_assoc($check_result);
    mysqli_stmt_close($check_stmt);
    
    $message = '';
    
    if ($existing) {
        if ($type == 'IN') {
            if ($existing['check_in_time']) {
                $message = 'Already checked in at ' . date('h:i A', strtotime($existing['check_in_time']));
                $success = false;
                $msg_type = 'warning';
            } else {
                $update_sql = "UPDATE tutor_attendance 
                              SET check_in_time = ?, status = 'Present', 
                                  is_biometric_verified = 1, fingerprint_match_score = ? 
                              WHERE tutor_id = ? AND attendance_date = ?";
                $update_stmt = mysqli_prepare($conn, $update_sql);
                mysqli_stmt_bind_param($update_stmt, "sdis", $datetime, $match_score, $tutor_id, $date);
                $success = mysqli_stmt_execute($update_stmt);
                mysqli_stmt_close($update_stmt);
                
                $message = 'Check-in successful at ' . date('h:i A');
                $msg_type = 'success';
            }
        } else {
            if (!$existing['check_in_time']) {
                $message = 'Please check in first';
                $success = false;
                $msg_type = 'warning';
            } elseif ($existing['check_out_time']) {
                $message = 'Already checked out at ' . date('h:i A', strtotime($existing['check_out_time']));
                $success = false;
                $msg_type = 'warning';
            } else {
                $update_sql = "UPDATE tutor_attendance 
                              SET check_out_time = ? 
                              WHERE tutor_id = ? AND attendance_date = ?";
                $update_stmt = mysqli_prepare($conn, $update_sql);
                mysqli_stmt_bind_param($update_stmt, "sis", $datetime, $tutor_id, $date);
                $success = mysqli_stmt_execute($update_stmt);
                mysqli_stmt_close($update_stmt);
                
                $message = 'Check-out successful at ' . date('h:i A');
                $msg_type = 'success';
            }
        }
    } else {
        if ($type == 'IN') {
            $insert_sql = "INSERT INTO tutor_attendance 
                          (tutor_id, attendance_date, check_in_time, status, is_biometric_verified, fingerprint_match_score) 
                          VALUES (?, ?, ?, 'Present', 1, ?)";
            $insert_stmt = mysqli_prepare($conn, $insert_sql);
            mysqli_stmt_bind_param($insert_stmt, "issd", $tutor_id, $date, $datetime, $match_score);
            $success = mysqli_stmt_execute($insert_stmt);
            mysqli_stmt_close($insert_stmt);
            
            $message = 'Check-in successful at ' . date('h:i A');
            $msg_type = 'success';
        } else {
            $message = 'Please check in first';
            $success = false;
            $msg_type = 'warning';
        }
    }
    
    // Log to biometric_logs
    if ($success) {
        $log_sql = "INSERT INTO biometric_logs 
                   (user_id, user_type, action_type, fingerprint_match_score, success, ip_address, device_info) 
                   VALUES (?, 'tutor', ?, ?, 1, ?, ?)";
        $log_stmt = mysqli_prepare($conn, $log_sql);
        $action = $type == 'IN' ? 'Check-In' : 'Check-Out';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $device = $_SERVER['HTTP_USER_AGENT'] ?? '';
        mysqli_stmt_bind_param($log_stmt, "isdss", $tutor_id, $action, $match_score, $ip, $device);
        mysqli_stmt_execute($log_stmt);
        mysqli_stmt_close($log_stmt);
    }
    
    return [
        'success' => $success,
        'type' => $msg_type,
        'response' => $message,
        'studentID' => $tutor['tutor_id'],
        'fullname' => $tutor['first_name'] . ' ' . $tutor['last_name'],
        'year' => 'Tutor',
        'course' => $tutor['specialization'] ?? 'N/A',
        'img' => $tutor['profile_picture'] ?? 'default.png',
        'match_score' => round($match_score, 2)
    ];
}
?>


