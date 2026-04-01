<?php
include "db_conn.php";

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_staff'])) {
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $department = mysqli_real_escape_string($conn, trim($_POST['department']));
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    echo "<h2>Debug Staff Registration</h2>\n";
    echo "<p>Form data received:</p>\n";
    echo "<ul>\n";
    echo "<li>Name: " . htmlspecialchars($name) . "</li>\n";
    echo "<li>Username: " . htmlspecialchars($username) . "</li>\n";
    echo "<li>Password: " . htmlspecialchars($password) . "</li>\n";
    echo "<li>Confirm Password: " . htmlspecialchars($confirm_password) . "</li>\n";
    echo "<li>Email: " . htmlspecialchars($email) . "</li>\n";
    echo "<li>Phone: " . htmlspecialchars($phone) . "</li>\n";
    echo "<li>Department: " . htmlspecialchars($department) . "</li>\n";
    echo "<li>Status: " . htmlspecialchars($status) . "</li>\n";
    echo "</ul>\n";
    
    if (empty($error_message)) {
        if (empty($name) || empty($username) || empty($password) || empty($confirm_password)) {
            $error_message = 'Please fill in all required fields.';
        } elseif ($password !== $confirm_password) {
            $error_message = 'Passwords do not match.';
        } elseif (strlen($password) < 4) {
            $error_message = 'Password must be at least 4 characters long.';
        } else {
            // Check if username already exists
            $check_sql = "SELECT id FROM users WHERE username = ?";
            $stmt = mysqli_prepare($conn, $check_sql);
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            $check_result = mysqli_stmt_get_result($stmt);
            
            echo "<p>Checking if username exists...</p>\n";
            echo "<p>Rows found: " . mysqli_num_rows($check_result) . "</p>\n";
            
            if (mysqli_num_rows($check_result) > 0) {
                $error_message = 'Username already exists. Please choose a different username.';
                mysqli_stmt_close($stmt);
            } else {
                mysqli_stmt_close($stmt);
                
                // Hash the password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                echo "<p>Password hashed: " . $hashed_password . "</p>\n";
                
                // Insert new staff user
                $insert_sql = "INSERT INTO users (role, username, password, name, email, phone, department, status) VALUES ('user', ?, ?, ?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $insert_sql);
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "sssssss", $username, $hashed_password, $name, $email, $phone, $department, $status);
                    echo "<p>Prepared statement created successfully</p>\n";
                    
                    if (mysqli_stmt_execute($stmt)) {
                        $success_message = 'Staff member added successfully!';
                        echo "<p>Staff member inserted successfully!</p>\n";
                        echo "<p>New user ID: " . mysqli_insert_id($conn) . "</p>\n";
                        $_POST = array();
                    } else {
                        $error_message = 'Error adding staff member: ' . mysqli_error($conn);
                        echo "<p>Error executing statement: " . mysqli_error($conn) . "</p>\n";
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    $error_message = 'Error preparing statement: ' . mysqli_error($conn);
                    echo "<p>Error preparing statement: " . mysqli_error($conn) . "</p>\n";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Debug Staff Registration</title>
</head>
<body>
    <h2>Debug Staff Registration Form</h2>
    
    <?php if ($success_message): ?>
        <div style="background-color: #d4edda; color: #155724; padding: 12px 20px; margin-bottom: 20px; border-radius: 4px;">
            <?php echo $success_message; ?>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div style="background-color: #f8d7da; color: #721c24; padding: 12px 20px; margin-bottom: 20px; border-radius: 4px;">
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div>
            <label for="name">Full Name *</label>
            <input type="text" id="name" name="name" required>
        </div>
        <div>
            <label for="username">Username *</label>
            <input type="text" id="username" name="username" required>
        </div>
        <div>
            <label for="password">Password *</label>
            <input type="password" id="password" name="password" required>
        </div>
        <div>
            <label for="confirm_password">Confirm Password *</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
        </div>
        <div>
            <label for="email">Email</label>
            <input type="email" id="email" name="email">
        </div>
        <div>
            <label for="phone">Phone</label>
            <input type="tel" id="phone" name="phone">
        </div>
        <div>
            <label for="department">Department</label>
            <input type="text" id="department" name="department">
        </div>
        <div>
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
                <option value="Suspended">Suspended</option>
            </select>
        </div>
        <div>
            <input type="submit" name="add_staff" value="Add Staff Member">
        </div>
    </form>
</body>
</html>