<?php
// Simple test to verify the student view query works
include "db_conn.php";

$sql = "SELECT s.*, y.year_level_name 
        FROM students s 
        LEFT JOIN year_levels y ON s.year_level_id = y.id 
        ORDER BY s.last_name, s.first_name";
$result = mysqli_query($conn, $sql);

if ($result) {
    echo "<h2>Student Query Test</h2>\n";
    echo "<p>Query executed successfully. Found " . mysqli_num_rows($result) . " students.</p>\n";
    
    if (mysqli_num_rows($result) > 0) {
        echo "<table border='1' cellpadding='5'>\n";
        echo "<tr><th>Student ID</th><th>Name</th><th>Email</th><th>Year Level</th><th>Status</th></tr>\n";
        
        while ($student = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($student['student_id']) . "</td>";
            echo "<td>" . htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) . "</td>";
            echo "<td>" . htmlspecialchars($student['email']) . "</td>";
            echo "<td>" . htmlspecialchars($student['year_level_name'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($student['status']) . "</td>";
            echo "</tr>\n";
        }
        
        echo "</table>\n";
    }
} else {
    echo "<p>Error executing query: " . mysqli_error($conn) . "</p>\n";
}
?>