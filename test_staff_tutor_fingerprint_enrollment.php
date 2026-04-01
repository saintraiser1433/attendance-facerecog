<?php
include "db_conn.php";

echo "<h2>Testing Staff & Tutor Fingerprint Enrollment Interface</h2>\n";

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

// Test tutors query
echo "<h3>Tutors Query Test</h3>\n";
$tutors_sql = "SELECT id, tutor_id, CONCAT(first_name, ' ', last_name) as name 
               FROM tutors 
               WHERE status = 'Active' 
               ORDER BY first_name, last_name
               LIMIT 5";
$tutors_result = mysqli_query($conn, $tutors_sql);

if ($tutors_result) {
    echo "<p>Tutors query executed successfully.</p>\n";
    echo "<p>Found " . mysqli_num_rows($tutors_result) . " active tutors.</p>\n";
    
    if (mysqli_num_rows($tutors_result) > 0) {
        echo "<ul>\n";
        while ($tutor = mysqli_fetch_assoc($tutors_result)) {
            echo "<li>" . htmlspecialchars($tutor['tutor_id'] . ' - ' . $tutor['name']) . "</li>\n";
        }
        echo "</ul>\n";
    }
} else {
    echo "<p>Error executing tutors query: " . mysqli_error($conn) . "</p>\n";
}

// Test fingerprint templates query
echo "<h3>Fingerprint Templates Query Test</h3>\n";
$fingerprint_sql = "SELECT ft.*, 
                           CASE 
                               WHEN ft.user_type = 'student' THEN s.student_id 
                               WHEN ft.user_type = 'staff' THEN u.username 
                               WHEN ft.user_type = 'tutor' THEN t.tutor_id
                               ELSE 'Unknown' 
                           END as user_identifier,
                           CASE 
                               WHEN ft.user_type = 'student' THEN CONCAT(s.first_name, ' ', s.last_name) 
                               WHEN ft.user_type = 'staff' THEN u.name 
                               WHEN ft.user_type = 'tutor' THEN CONCAT(t.first_name, ' ', t.last_name)
                               ELSE 'Unknown User' 
                           END as user_name
                    FROM fingerprint_templates ft
                    LEFT JOIN students s ON ft.user_id = s.id AND ft.user_type = 'student'
                    LEFT JOIN users u ON ft.user_id = u.id AND ft.user_type = 'staff'
                    LEFT JOIN tutors t ON ft.user_id = t.id AND ft.user_type = 'tutor'
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
echo "<p>To access the updated fingerprint enrollment interface:</p>\n";
echo "<ul>\n";
echo "<li>Log in as admin (username: elias, password: 1234)</li>\n";
echo "<li>Navigate to 'Manage Tutors & Staff' > 'Enroll Staff & Tutor Fingerprint'</li>\n";
echo "</ul>\n";

echo "<h3>New Features</h3>\n";
echo "<ul>\n";
echo "<li>Unified interface for both staff and tutor fingerprint enrollment</li>\n";
echo "<li>User type selection (Staff or Tutor)</li>\n";
echo "<li>Dynamic user lists based on selected user type</li>\n";
echo "<li>Support for both staff and tutor fingerprint management</li>\n";
echo "</ul>\n";
?>