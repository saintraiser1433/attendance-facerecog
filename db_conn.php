<?php  

// Set timezone to Asia/Manila
date_default_timezone_set('Asia/Manila');

$sname = "localhost";
$uname = "root";
$password = "";

$db_name = "testdb";

// Enable error reporting for mysqli
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = mysqli_connect($sname, $uname, $password, $db_name);
    
    // Set charset to UTF-8 for proper character handling
    mysqli_set_charset($conn, "utf8mb4");
    
    // Set MySQL session timezone to match PHP timezone (Asia/Manila = UTC+8)
    mysqli_query($conn, "SET time_zone = '+08:00'");
    
} catch (Exception $e) {
    // Log error (in production, log to file instead of displaying)
    error_log("Database Connection Error: " . $e->getMessage());
    
    // Display user-friendly message
    die("Database connection failed. Please contact the administrator.");
}