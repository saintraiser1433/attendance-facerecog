<?php
include "db_conn.php";

$sql = "SELECT id, username, role, name FROM users WHERE role = 'user'";
$result = mysqli_query($conn, $sql);

echo "Staff accounts in database:\n";
while ($row = mysqli_fetch_assoc($result)) {
    echo "ID: " . $row['id'] . ", Username: " . $row['username'] . ", Name: " . $row['name'] . "\n";
}

echo "\nAll users:\n";
$sql = "SELECT id, username, role, name FROM users";
$result = mysqli_query($conn, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    echo "ID: " . $row['id'] . ", Username: " . $row['username'] . ", Role: " . $row['role'] . ", Name: " . $row['name'] . "\n";
}
?>