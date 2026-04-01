<?php
include "db_conn.php";

// Test if we can retrieve a staff user from the database
echo "Testing database connection and query...\n";

$sql = "SELECT id, username, role, name, password FROM users WHERE role = 'user' LIMIT 1";
$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    echo "Found staff user:\n";
    echo "ID: " . $row['id'] . "\n";
    echo "Username: " . $row['username'] . "\n";
    echo "Role: " . $row['role'] . "\n";
    echo "Name: " . $row['name'] . "\n";
    echo "Password hash: " . $row['password'] . "\n";
    
    // Test password verification
    $test_password = 'abcd'; // Default password for john
    if (password_verify($test_password, $row['password'])) {
        echo "Password verification: SUCCESS (modern hash)\n";
    } elseif (md5($test_password) === $row['password']) {
        echo "Password verification: SUCCESS (legacy MD5)\n";
    } else {
        echo "Password verification: FAILED\n";
    }
} else {
    echo "No staff users found in database\n";
}

// Test inserting a new staff user
echo "\nTesting insertion of new staff user...\n";
$username = 'teststaff';
$password = 'testpass';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$name = 'Test Staff';
$email = 'test@example.com';

$insert_sql = "INSERT INTO users (role, username, password, name, email, status) VALUES ('user', ?, ?, ?, ?, 'Active')";
$stmt = mysqli_prepare($conn, $insert_sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "sssss", $username, $hashed_password, $name, $email);
    if (mysqli_stmt_execute($stmt)) {
        echo "New staff user inserted successfully\n";
        
        // Test if we can retrieve it
        $select_sql = "SELECT id, username, role, name FROM users WHERE username = ?";
        $select_stmt = mysqli_prepare($conn, $select_sql);
        mysqli_stmt_bind_param($select_stmt, "s", $username);
        mysqli_stmt_execute($select_stmt);
        $select_result = mysqli_stmt_get_result($select_stmt);
        
        if ($select_result && mysqli_num_rows($select_result) > 0) {
            $user = mysqli_fetch_assoc($select_result);
            echo "Retrieved new user:\n";
            echo "ID: " . $user['id'] . "\n";
            echo "Username: " . $user['username'] . "\n";
            echo "Role: " . $user['role'] . "\n";
            echo "Name: " . $user['name'] . "\n";
        }
        
        // Clean up - delete the test user
        $delete_sql = "DELETE FROM users WHERE username = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_sql);
        mysqli_stmt_bind_param($delete_stmt, "s", $username);
        mysqli_stmt_execute($delete_stmt);
        mysqli_stmt_close($delete_stmt);
        
        echo "Test user cleaned up\n";
    } else {
        echo "Error inserting new staff user: " . mysqli_error($conn) . "\n";
    }
    mysqli_stmt_close($stmt);
} else {
    echo "Error preparing insert statement: " . mysqli_error($conn) . "\n";
}
?>