<?php
session_start();
include "db_conn.php";

if (isset($_POST['username']) && isset($_POST['password'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (empty($username)) {
        header("Location: tutor_login.php?error=Username is required");
        exit();
    } else if (empty($password)) {
        header("Location: tutor_login.php?error=Password is required");
        exit();
    } else {
        // Check if tutor exists
        $sql = "SELECT t.*, tl.password FROM tutors t 
                LEFT JOIN tutor_logins tl ON t.id = tl.tutor_id 
                WHERE tl.username = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $tutor = $result->fetch_assoc();
            
            // Verify password (in a real app, use password_verify() with hashed passwords)
            if ($password === $tutor['password']) {
                // Set session variables
                $_SESSION['tutor_id'] = $tutor['id'];
                $_SESSION['tutor_name'] = $tutor['first_name'] . ' ' . $tutor['last_name'];
                $_SESSION['tutor_email'] = $tutor['email'];
                
                // Update last login
                $update_sql = "UPDATE tutor_logins SET last_login = NOW() WHERE tutor_id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("i", $tutor['id']);
                $update_stmt->execute();
                $update_stmt->close();
                
                // Redirect to tutor dashboard
                header("Location: tutor/dashboard.php");
                exit();
            } else {
                header("Location: tutor_login.php?error=Incorrect username or password");
                exit();
            }
        } else {
            header("Location: tutor_login.php?error=Incorrect username or password");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Tutor Login - Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
            padding: 40px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h1 {
            color: #f5576c;
            font-weight: bold;
            margin: 0;
        }
        .form-control {
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .form-control:focus {
            border-color: #f5576c;
            box-shadow: 0 0 0 3px rgba(245, 87, 108, 0.1);
        }
        .btn-login {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            border: none;
            padding: 12px;
            width: 100%;
            border-radius: 8px;
            font-weight: 600;
            margin-top: 10px;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(245, 87, 108, 0.3);
        }
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        .back-link a {
            color: white;
            text-decoration: none;
        }
        .error-message {
            color: #e74c3c;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <i class="fas fa-chalkboard-teacher" style="font-size: 3em; color: #f5576c; margin-bottom: 15px;"></i>
            <h1>Tutor Login</h1>
            <p>Access your tutor account</p>
        </div>

        <?php if (isset($_GET['error'])) { ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php } ?>

        <form method="POST" action="">
            <div class="form-group">
                <input type="text" 
                       class="form-control" 
                       name="username" 
                       placeholder="Username" 
                       required>
            </div>
            <div class="form-group">
                <input type="password" 
                       class="form-control" 
                       name="password" 
                       placeholder="Password" 
                       required>
            </div>
            <button type="submit" class="btn btn-login">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>

        <div class="back-link">
            <a href="index.php"><i class="fas fa-arrow-left"></i> Back to Main Login</a>
        </div>
    </div>
</body>
</html>
