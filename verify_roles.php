<?php
include "db_conn.php";

echo "<h2>Current Users in Database</h2>\n";

$sql = "SELECT id, username, role, name FROM users ORDER BY role, id";
$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    echo "<table border='1' cellpadding='5' cellspacing='0'>\n";
    echo "<tr><th>ID</th><th>Username</th><th>Role</th><th>Name</th></tr>\n";
    
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
        echo "<td>" . htmlspecialchars($row['role']) . "</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "</tr>\n";
    }
    
    echo "</table>\n";
} else {
    echo "<p>No users found in database</p>\n";
}

echo "<h2>Role Mapping in System</h2>\n";
echo "<ul>\n";
echo "<li>Admin users (role='admin') → admin/dashboard.php</li>\n";
echo "<li>Staff users (role='user') → staff/dashboard.php</li>\n";
echo "<li>Teacher users (role='teacher') → teacher/dashboard.php</li>\n";
echo "<li>Student users (role='student') → student/dashboard.php</li>\n";
echo "</ul>\n";

echo "<h2>Test Login Process</h2>\n";
echo "<p>To test login, go to <a href='index.php'>index.php</a> and use one of these accounts:</p>\n";
echo "<ul>\n";
echo "<li>Admin: username 'elias', password '1234'</li>\n";
echo "<li>Staff: username 'john', password 'abcd'</li>\n";
echo "</ul>\n";
?>