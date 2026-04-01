<?php
include "db_conn.php";

echo "<h2>Testing Fingerprint Enrollment Interfaces</h2>\n";

// Test students query
echo "<h3>Students Query Test</h3>\n";
$students_sql = "SELECT id, student_id, CONCAT(first_name, ' ', last_name) as name 
                 FROM students 
                 WHERE status = 'Active' 
                 ORDER BY last_name, first_name
                 LIMIT 5";
$students_result = mysqli_query($conn, $students_sql);

if ($students_result) {
    echo "<p>Students query executed successfully.</p>\n";
    echo "<p>Found " . mysqli_num_rows($students_result) . " active students.</p>\n";
    
    if (mysqli_num_rows($students_result) > 0) {
        echo "<ul>\n";
        while ($student = mysqli_fetch_assoc($students_result)) {
            echo "<li>" . htmlspecialchars($student['student_id'] . ' - ' . $student['name']) . "</li>\n";
        }
        echo "</ul>\n";
    }
} else {
    echo "<p>Error executing students query: " . mysqli_error($conn) . "</p>\n";
}

// Test staff query
echo "<h3>Staff Query Test</h3>\n";
$staff_sql = "SELECT id, username, name 
              FROM users 
              WHERE role = 'user' AND status != 'Suspended' 
              ORDER BY name
              LIMIT 5";
$staff_result = mysqli_query($conn, $staff_sql);

if ($staff_result) {
    echo "<p>Staff query executed successfully.</p>\n";
    echo "<p>Found " . mysqli_num_rows($staff_result) . " active staff members.</p>\n";
    
    if (mysqli_num_rows($staff_result) > 0) {
        echo "<ul>\n";
        while ($staff = mysqli_fetch_assoc($staff_result)) {
            echo "<li>" . htmlspecialchars($staff['username'] . ' - ' . $staff['name']) . "</li>\n";
        }
        echo "</ul>\n";
    }
} else {
    echo "<p>Error executing staff query: " . mysqli_error($conn) . "</p>\n";
}

// Test fingerprint templates query
echo "<h3>Fingerprint Templates Query Test</h3>\n";
$fingerprint_sql = "SELECT ft.*, 
                           CASE 
                               WHEN ft.user_type = 'student' THEN s.student_id 
                               WHEN ft.user_type = 'staff' THEN u.username 
                               ELSE 'Unknown' 
                           END as user_identifier,
                           CASE 
                               WHEN ft.user_type = 'student' THEN CONCAT(s.first_name, ' ', s.last_name) 
                               WHEN ft.user_type = 'staff' THEN u.name 
                               ELSE 'Unknown User' 
                           END as user_name
                    FROM fingerprint_templates ft
                    LEFT JOIN students s ON ft.user_id = s.id AND ft.user_type = 'student'
                    LEFT JOIN users u ON ft.user_id = u.id AND ft.user_type = 'staff'
                    ORDER BY ft.updated_at DESC
                    LIMIT 5";
$fingerprint_result = mysqli_query($conn, $fingerprint_sql);

if ($fingerprint_result) {
    echo "<p>Fingerprint templates query executed successfully.</p>\n";
    echo "<p>Found " . mysqli_num_rows($fingerprint_result) . " fingerprint templates.</p>\n";
    
    if (mysqli_num_rows($fingerprint_result) > 0) {
        echo "<table border='1' cellpadding='5'>\n";
        echo "<tr><th>User Type</th><th>User ID</th><th>User Name</th><th>Created</th><th>Updated</th></tr>\n";
        
        while ($template = mysqli_fetch_assoc($fingerprint_result)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($template['user_type']) . "</td>";
            echo "<td>" . htmlspecialchars($template['user_identifier']) . "</td>";
            echo "<td>" . htmlspecialchars($template['user_name']) . "</td>";
            echo "<td>" . htmlspecialchars($template['created_at']) . "</td>";
            echo "<td>" . htmlspecialchars($template['updated_at']) . "</td>";
            echo "</tr>\n";
        }
        
        echo "</table>\n";
    }
} else {
    echo "<p>Error executing fingerprint templates query: " . mysqli_error($conn) . "</p>\n";
}

echo "<h3>Interface Access</h3>\n";
echo "<p>To access the fingerprint enrollment interfaces:</p>\n";
echo "<ul>\n";
echo "<li>Log in as admin (username: elias, password: 1234)</li>\n";
echo "<li>Navigate to 'Manage Students' > 'Enroll Fingerprint' for student fingerprint enrollment</li>\n";
echo "<li>Navigate to 'Manage Tutors & Staff' > 'Enroll Staff & Tutor Fingerprint' for staff and tutor fingerprint enrollment</li>\n";
echo "</ul>\n";

echo "<h3>Fingerprint Enrollment Features</h3>\n";
echo "<ul>\n";
echo "<li>User selection dropdown for both students and staff</li>\n";
echo "<li>Fingerprint template data entry</li>\n";
echo "<li>Existing fingerprint detection and update capability</li>\n";
echo "<li>Biometric logging for all enrollment activities</li>\n";
echo "<li>Simulation functionality for testing</li>\n";
echo "</ul>\n";
?>