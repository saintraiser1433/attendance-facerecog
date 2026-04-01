<?php 
session_start();

if (!isset($_SESSION['username']) || !isset($_SESSION['id']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../index.php");
    exit();
}

include "../db_conn.php";

$current_page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$teacher_name = $_SESSION['name'];
$teacher_id = $_SESSION['id'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Teacher Dashboard - Attendance System</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
                <h3><i class="fas fa-chalkboard-teacher"></i> Teacher Portal</h3>
                <p><?php echo htmlspecialchars($teacher_name); ?></p>
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
                <div class="menu-header">Attendance Management</div>
                <a href="?page=create_session" class="menu-item <?php echo $current_page == 'create_session' ? 'active' : ''; ?>">
                    <i class="fas fa-plus-circle"></i>
                    <span>Create Session</span>
                </a>
                <a href="?page=my_sessions" class="menu-item <?php echo $current_page == 'my_sessions' ? 'active' : ''; ?>">
                    <i class="fas fa-list"></i>
                    <span>My Sessions</span>
                </a>
                <a href="?page=mark_attendance" class="menu-item <?php echo $current_page == 'mark_attendance' ? 'active' : ''; ?>">
                    <i class="fas fa-check-square"></i>
                    <span>Mark Attendance</span>
                </a>
                <a href="?page=view_attendance" class="menu-item <?php echo $current_page == 'view_attendance' ? 'active' : ''; ?>">
                    <i class="fas fa-eye"></i>
                    <span>View Attendance</span>
                </a>
            </div>

            <div class="menu-section">
                <div class="menu-header">Reports</div>
                <a href="?page=attendance_reports" class="menu-item <?php echo $current_page == 'attendance_reports' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar"></i>
                    <span>Attendance Reports</span>
                </a>
            </div>

            <div class="menu-section">
                <div class="menu-header">Account</div>
                <a href="?page=profile" class="menu-item <?php echo $current_page == 'profile' ? 'active' : ''; ?>">
                    <i class="fas fa-user"></i>
                    <span>My Profile</span>
                </a>
            </div>
        </div>

        <div class="main-content">
            <div class="top-bar">
                <h2><?php 
                    $titles = [
                        'dashboard' => 'Dashboard',
                        'announcements' => 'Announcements',
                        'create_session' => 'Create Attendance Session',
                        'my_sessions' => 'My Sessions',
                        'mark_attendance' => 'Mark Attendance',
                        'view_attendance' => 'View Attendance',
                        'attendance_reports' => 'Attendance Reports',
                        'profile' => 'My Profile'
                    ];
                    echo $titles[$current_page] ?? 'Dashboard';
                ?></h2>
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($teacher_name, 0, 1)); ?>
                    </div>
                    <span><?php echo htmlspecialchars($teacher_name); ?></span>
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
                    case 'create_session':
                        include 'content_create_session.php';
                        break;
                    case 'my_sessions':
                        include 'content_my_sessions.php';
                        break;
                    case 'mark_attendance':
                        include 'content_mark_attendance.php';
                        break;
                    case 'view_attendance':
                        include 'content_view_attendance.php';
                        break;
                    case 'attendance_reports':
                        include 'content_attendance_reports.php';
                        break;
                    case 'profile':
                        include 'content_profile.php';
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
