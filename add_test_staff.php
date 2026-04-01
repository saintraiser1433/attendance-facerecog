<?php
include "db_conn.php";

// Add a test staff member
$username = 'teststaff';
$password = 'abcd';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$name = 'Test Staff';
$email = 'test@example.com';

$sql = "INSERT INTO users (role, username, password, name, email, status) VALUES ('user', ?, ?, ?, ?, 'Active')";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ssss", $username, $hashed_password, $name, $email);

if (mysqli_stmt_execute($stmt)) {
    echo "Test staff member added successfully!\n";
    echo "Username: teststaff\n";
    echo "Password: abcd\n";
} else {
    echo "Error adding test staff member: " . mysqli_error($conn) . "\n";
}

mysqli_stmt_close($stmt);
?>