<?php
/**
 * Fix Fingerprint System Script
 * This script fixes common issues with the DigitalPersona fingerprint system
 */
session_start();
include "db_conn.php";

$fixes_applied = [];
$errors = [];

// Fix 1: Ensure the fingerprint_templates table has the correct structure
function fixFingerprintTable($conn, &$fixes_applied, &$errors) {
    // Check current table structure
    $sql = "DESCRIBE fingerprint_templates";
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        $errors[] = "Cannot describe fingerprint_templates table: " . mysqli_error($conn);
        return;
    }
    
    $columns = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $columns[$row['Field']] = $row;
    }
    
    // Check if user_type column exists
    if (!isset($columns['user_type'])) {
        // Add user_type column
        $sql = "ALTER TABLE fingerprint_templates ADD COLUMN user_type ENUM('student', 'staff', 'tutor') NOT NULL AFTER user_id";
        if (mysqli_query($conn, $sql)) {
            $fixes_applied[] = "Added user_type column to fingerprint_templates table";
        } else {
            $errors[] = "Failed to add user_type column: " . mysqli_error($conn);
        }
        
        // Update unique key to include user_type
        $sql = "ALTER TABLE fingerprint_templates DROP INDEX unique_user_fingerprint";
        mysqli_query($conn, $sql); // Ignore error if index doesn't exist
        
        $sql = "ALTER TABLE fingerprint_templates ADD UNIQUE KEY unique_user_fingerprint (user_id, user_type)";
        if (mysqli_query($conn, $sql)) {
            $fixes_applied[] = "Updated unique key to include user_type";
        } else {
            $errors[] = "Failed to update unique key: " . mysqli_error($conn);
        }
    } else {
        $fixes_applied[] = "fingerprint_templates table structure is correct";
    }
}

// Fix 2: Check if required JavaScript files exist
function checkJavaScriptFiles(&$fixes_applied, &$errors) {
    $required_files = [
        'Fingerprint/StudentFingerPrint/StudentFingerPrint/lib/js/websdk.client.bundle.min.js',
        'js/fingerprint_handler.js'
    ];
    
    foreach ($required_files as $file) {
        if (file_exists($file)) {
            $fixes_applied[] = "Found required file: $file";
        } else {
            $errors[] = "Missing required file: $file";
        }
    }
}

// Fix 3: Check if required API endpoints exist
function checkApiEndpoints(&$fixes_applied, &$errors) {
    $required_endpoints = [
        'php/fingerprint/api/get_templates_by_type.php',
        'php/fingerprint/api/enroll.php',
        'php/fingerprint/api/verify.php'
    ];
    
    foreach ($required_endpoints as $endpoint) {
        if (file_exists($endpoint)) {
            $fixes_applied[] = "Found required API endpoint: $endpoint";
        } else {
            $errors[] = "Missing required API endpoint: $endpoint";
        }
    }
}

// Apply fixes
fixFingerprintTable($conn, $fixes_applied, $errors);
checkJavaScriptFiles($fixes_applied, $errors);
checkApiEndpoints($fixes_applied, $errors);

mysqli_close($conn);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fingerprint System Fix</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-12">
                <h1><i class="fas fa-tools"></i> Fingerprint System Fix</h1>
                <p class="lead">This script checks and fixes common issues with the DigitalPersona fingerprint system.</p>
                
                <?php if (count($errors) > 0): ?>
                    <div class="alert alert-danger">
                        <h4><i class="fas fa-exclamation-triangle"></i> Errors Found</h4>
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if (count($fixes_applied) > 0): ?>
                    <div class="alert alert-success">
                        <h4><i class="fas fa-check-circle"></i> Fixes Applied</h4>
                        <ul>
                            <?php foreach ($fixes_applied as $fix): ?>
                                <li><?php echo htmlspecialchars($fix); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if (count($errors) == 0 && count($fixes_applied) > 0): ?>
                    <div class="alert alert-info">
                        <h4><i class="fas fa-thumbs-up"></i> System Status</h4>
                        <p>All checks passed. The fingerprint system should now be working correctly.</p>
                    </div>
                <?php elseif (count($errors) == 0 && count($fixes_applied) == 0): ?>
                    <div class="alert alert-info">
                        <h4><i class="fas fa-info-circle"></i> System Status</h4>
                        <p>No issues found. The fingerprint system appears to be configured correctly.</p>
                    </div>
                <?php endif; ?>
                
                <div class="card mt-4">
                    <div class="card-header">
                        <h5><i class="fas fa-list-check"></i> Next Steps</h5>
                    </div>
                    <div class="card-body">
                        <ol>
                            <li>Test the fingerprint system by visiting the <a href="comprehensive_fingerprint_test.php">comprehensive test page</a></li>
                            <li>If you still encounter issues, check that your DigitalPersona fingerprint reader is properly connected</li>
                            <li>Ensure your browser supports the required plugins for the DigitalPersona WebSDK</li>
                            <li>Check the browser console for any JavaScript errors</li>
                        </ol>
                    </div>
                </div>
                
                <div class="mt-4">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-home"></i> Return to Home
                    </a>
                    <a href="comprehensive_fingerprint_test.php" class="btn btn-primary">
                        <i class="fas fa-vial"></i> Run Comprehensive Test
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>