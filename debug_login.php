<?php
session_start();
include "db_conn.php";

if (isset($_POST['username']) && isset($_POST['password'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    echo "<h2>Debug Login Information</h2>\n";
    echo "<p>Username provided: " . htmlspecialchars($username) . "</p>\n";
    echo "<p>Password provided: " . htmlspecialchars($password) . "</p>\n";
    
    // Use prepared statements to prevent SQL injection
    $sql = "SELECT * FROM users WHERE username=? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    echo "<p>Database query executed</p>\n";
    echo "<p>Number of rows found: " . mysqli_num_rows($result) . "</p>\n";
    
    if ($result && mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);
        echo "<p>User found in database:</p>\n";
        echo "<ul>\n";
        echo "<li>ID: " . $row['id'] . "</li>\n";
        echo "<li>Username: " . $row['username'] . "</li>\n";
        echo "<li>Role: " . $row['role'] . "</li>\n";
        echo "<li>Name: " . $row['name'] . "</li>\n";
        echo "<li>Password hash: " . $row['password'] . "</li>\n";
        echo "</ul>\n";
        
        $stored_hash = $row['password'];
        $valid_password = false;
        
        // Support modern password_hash hashed passwords
        if (password_verify($password, $stored_hash)) {
            $valid_password = true;
            echo "<p>Password verification: SUCCESS (modern hash)</p>\n";
        }
        // Legacy support for old MD5 hashed passwords
        elseif ($stored_hash === md5($password)) {
            $valid_password = true;
            echo "<p>Password verification: SUCCESS (legacy MD5)</p>\n";
        } else {
            echo "<p>Password verification: FAILED</p>\n";
        }
        
        if ($valid_password) {
            echo "<p>Setting session variables...</p>\n";
            $_SESSION['name'] = $row['name'];
            $_SESSION['id'] = $row['id'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['username'] = $row['username'];
            
            echo "<p>Session variables set:</p>\n";
            echo "<ul>\n";
            echo "<li>Name: " . $_SESSION['name'] . "</li>\n";
            echo "<li>ID: " . $_SESSION['id'] . "</li>\n";
            echo "<li>Role: " . $_SESSION['role'] . "</li>\n";
            echo "<li>Username: " . $_SESSION['username'] . "</li>\n";
            echo "</ul>\n";
            
            echo "<p>Redirecting to home.php...</p>\n";
            // Don't actually redirect for debugging
            // header("Location: home.php");
        }
    } else {
        echo "<p>User not found in database</p>\n";
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Debug Login</title>
</head>
<body>
    <h2>Debug Login Form</h2>
    <form method="POST" action="">
        <div>
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
        </div>
        <div>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
        </div>
        <div>
            <input type="submit" value="Login">
        </div>
    </form>
    
    <h3>Test Accounts</h3>
    <p>Admin: username: elias, password: 1234</p>
    <p>Staff: username: john, password: abcd</p>
</body>
</html>