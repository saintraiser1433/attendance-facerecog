<?php
include "db_conn.php";

// Test database connection and query
$username = 'john';
$password = 'abcd';

echo "Testing login for user: $username with password: $password\n";

$sql = "SELECT * FROM users WHERE username=? LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

echo "Number of rows found: " . mysqli_num_rows($result) . "\n";

if ($result && mysqli_num_rows($result) === 1) {
    $row = mysqli_fetch_assoc($result);
    echo "User found:\n";
    echo "ID: " . $row['id'] . "\n";
    echo "Username: " . $row['username'] . "\n";
    echo "Role: " . $row['role'] . "\n";
    echo "Name: " . $row['name'] . "\n";
    echo "Stored password hash: " . $row['password'] . "\n";
    
    $stored_hash = $row['password'];
    
    // Test password verification
    if (password_verify($password, $stored_hash)) {
        echo "Password verified with password_hash\n";
    } elseif ($stored_hash === md5($password)) {
        echo "Password verified with MD5\n";
    } else {
        echo "Password verification failed\n";
    }
} else {
    echo "User not found\n";
}

mysqli_stmt_close($stmt);
?>