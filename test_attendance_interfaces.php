<?php
include "db_conn.php";

echo "<h2>Testing Attendance Interfaces</h2>\n";

// Test student attendance query
echo "<h3>Student Attendance Query Test</h3>\n";
$date = date('Y-m-d');
$sql = "SELECT s.student_id, s.first_name, s.last_name, s.section, y.year_level_name, 
               sa.attendance_date, sa.status, sa.check_in_time, sa.check_out_time
        FROM students s
        LEFT JOIN year_levels y ON s.year_level_id = y.id
        LEFT JOIN student_attendance sa ON s.id = sa.student_id AND sa.attendance_date = ?
        WHERE s.status = 'Active'
        ORDER BY y.order_number, s.section, s.last_name, s.first_name
        LIMIT 5";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $date);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result) {
    echo "<p>Student attendance query executed successfully.</p>\n";
    echo "<p>Found " . mysqli_num_rows($result) . " student records.</p>\n";
} else {
    echo "<p>Error executing student attendance query: " . mysqli_error($conn) . "</p>\n";
}

// Test staff attendance query
echo "<h3>Staff Attendance Query Test</h3>\n";
$sql = "SELECT u.id, u.username, u.name, u.status as user_status,
               sa.attendance_date, sa.status, sa.check_in_time, sa.check_out_time
        FROM users u
        LEFT JOIN staff_attendance sa ON u.id = sa.staff_id AND sa.attendance_date = ?
        WHERE u.role = 'user' AND u.status != 'Suspended'
        ORDER BY u.name
        LIMIT 5";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $date);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result) {
    echo "<p>Staff attendance query executed successfully.</p>\n";
    echo "<p>Found " . mysqli_num_rows($result) . " staff records.</p>\n";
} else {
    echo "<p>Error executing staff attendance query: " . mysqli_error($conn) . "</p>\n";
}

echo "<h3>Interface Access</h3>\n";
echo "<p>To access the attendance interfaces:</p>\n";
echo "<ul>\n";
echo "<li>Log in as admin (username: elias, password: 1234)</li>\n";
echo "<li>Navigate to 'Manage Students' > 'View Attendance' for student attendance</li>\n";
echo "<li>Navigate to 'Manage Tutors & Staff' > 'View Staff Attendance' for staff attendance</li>\n";
echo "<li>Navigate to 'Manage Tutors' > 'Tutor Attendance' for tutor attendance</li>\n";
echo "</ul>\n";
?>