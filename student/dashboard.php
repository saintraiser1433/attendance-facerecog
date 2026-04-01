<?php 
session_start();

if (!isset($_SESSION['username']) || !isset($_SESSION['id']) || $_SESSION['role'] != 'student') {
    header("Location: ../index.php");
    exit();
}

include "../db_conn.php";

$current_page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$student_name = $_SESSION['name'];
$student_id = $_SESSION['id'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Student Dashboard - Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
        }
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 260px;
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: all 0.3s;
            z-index: 1000;
        }
        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar-header h3 {
            font-size: 1.3em;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .sidebar-header p {
            font-size: 0.85em;
            opacity: 0.9;
            margin: 0;
        }
        .menu-section {
            padding: 15px 0;
        }
        .menu-header {
            padding: 10px 20px;
            font-size: 0.75em;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.7;
            font-weight: 600;
        }
        .menu-item {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
        }
        .menu-item:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            padding-left: 30px;
        }
        .menu-item.active {
            background: rgba(255,255,255,0.2);
            border-left: 4px solid white;
        }
        .menu-item i {
            width: 25px;
            margin-right: 12px;
            font-size: 1.1em;
        }
        .main-content {
            margin-left: 260px;
            flex: 1;
            padding: 30px;
            width: calc(100% - 260px);
        }
        .top-bar {
            background: white;
            padding: 20px 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .top-bar h2 {
            margin: 0;
            color: #2c3e50;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        .logout-btn {
            background: #e74c3c;
            color: white;
            padding: 8px 20px;
            border-radius: 4px;
            text-decoration: none;
            transition: all 0.3s;
        }
        .logout-btn:hover {
            background: #c0392b;
            color: white;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-user-graduate"></i> Student Portal</h3>
                <p><?php echo htmlspecialchars($student_name); ?></p>
            </div>
            
            <div class="menu-section">
                <div class="menu-header">Main</div>
                <a href="?page=dashboard" class="menu-item <?php echo $current_page == 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="?page=announcements" class="menu-item <?php echo $current_page == 'announcements' ? 'active' : ''; ?>">
                    <i class="fas fa-bullhorn"></i>
                    <span>Announcements</span>
                </a>
            </div>

            <div class="menu-section">
                <div class="menu-header">Attendance</div>
                <a href="?page=mark_attendance" class="menu-item <?php echo $current_page == 'mark_attendance' ? 'active' : ''; ?>">
                    <i class="fas fa-check-circle"></i>
                    <span>Mark Attendance</span>
                </a>
                <a href="?page=fingerprint_scan" class="menu-item <?php echo $current_page == 'fingerprint_scan' ? 'active' : ''; ?>">
                    <i class="fas fa-fingerprint"></i>
                    <span>Fingerprint Scan</span>
                </a>
                <a href="?page=my_attendance" class="menu-item <?php echo $current_page == 'my_attendance' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-check"></i>
                    <span>My Attendance</span>
                </a>
                <a href="?page=attendance_history" class="menu-item <?php echo $current_page == 'attendance_history' ? 'active' : ''; ?>">
                    <i class="fas fa-history"></i>
                    <span>Attendance History</span>
                </a>
            </div>

            <div class="menu-section">
                <div class="menu-header">Account</div>
                <a href="?page=profile" class="menu-item <?php echo $current_page == 'profile' ? 'active' : ''; ?>">
                    <i class="fas fa-user"></i>
                    <span>My Profile</span>
                </a>
                <a href="?page=qr_code" class="menu-item <?php echo $current_page == 'qr_code' ? 'active' : ''; ?>">
                    <i class="fas fa-qrcode"></i>
                    <span>My QR Code</span>
                </a>
            </div>
        </div>

        <div class="main-content">
            <div class="top-bar">
                <h2><?php 
                    $titles = [
                        'dashboard' => 'Dashboard',
                        'announcements' => 'Announcements',
                        'mark_attendance' => 'Mark Attendance',
                        'my_attendance' => 'My Attendance',
                        'attendance_history' => 'Attendance History',
                        'fingerprint_scan' => 'Fingerprint Scan',
                        'profile' => 'My Profile',
                        'qr_code' => 'My QR Code'
                    ];
                    echo $titles[$current_page] ?? 'Dashboard';
                ?></h2>
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($student_name, 0, 1)); ?>
                    </div>
                    <span><?php echo htmlspecialchars($student_name); ?></span>
                    <a href="../logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>

            <div class="content-area">
                <?php
                switch($current_page) {
                    case 'announcements':
                        include 'content_announcements.php';
                        break;
                    case 'mark_attendance':
                        include 'content_mark_attendance.php';
                        break;
                    case 'my_attendance':
                        include 'content_my_attendance.php';
                        break;
                    case 'fingerprint_scan':
                        include 'content_scan_fingerprint.php';
                        break;
                    case 'attendance_history':
                        include 'content_attendance_history.php';
                        break;
                    case 'profile':
                        include 'content_profile.php';
                        break;
                    case 'qr_code':
                        include 'content_qr_code.php';
                        break;
                    default:
                        include 'content_dashboard.php';
                }
                ?>
            </div>
        </div>
    </div>
</body>
</html>
<?php 
   } else {
       // Clear session and redirect to login
       session_unset();
       session_destroy();
       header("Location: ../index.php");
       exit;
   }
?>
