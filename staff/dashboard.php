<?php 
   session_start();
   include "../db_conn.php";
   if (isset($_SESSION['username']) && isset($_SESSION['id']) && $_SESSION['role'] == 'user') {
       // Set the current page based on the 'page' parameter in the URL
       $current_page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
       $page_title = ucwords(str_replace('_', ' ', $current_page));
   ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - Attendance System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 250px;
            background: #2c3e50;
            color: #ecf0f1;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            display: flex;
            flex-direction: column;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            transition: width 0.3s;
        }
        .sidebar.collapsed {
            width: 70px;
        }
        .logo {
            padding: 20px;
            font-size: 1.5em;
            font-weight: bold;
            color: #fff;
            text-align: center;
            border-bottom: 1px solid #34495e;
            text-decoration: none;
            display: block;
        }
        .menu {
            flex: 1;
            overflow-y: auto;
            padding: 20px 0;
        }
        .menu-section {
            margin-bottom: 20px;
        }
        .menu-header {
            padding: 10px 20px;
            font-size: 0.8em;
            text-transform: uppercase;
            color: #7f8c8d;
            font-weight: bold;
            letter-spacing: 1px;
        }
        .menu-item {
            padding: 12px 20px;
            color: #ecf0f1;
            display: flex;
            align-items: center;
            transition: all 0.3s;
            border-left: 3px solid transparent;
            text-decoration: none;
        }
        .menu-item.active {
            background: #34495e;
            border-left: 3px solid #3498db;
        }
        .menu-item:hover {
            background: #34495e;
            border-left: 3px solid #3498db;
            text-decoration: none;
            color: #ecf0f1;
        }
        .menu-item i {
            width: 25px;
            margin-right: 10px;
            text-align: center;
        }
        .menu-footer {
            padding: 15px 20px;
            border-top: 1px solid #34495e;
        }
        .main-content {
            flex: 1;
            margin-left: 250px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: margin-left 0.3s;
        }
        .sidebar.collapsed ~ .main-content {
            margin-left: 70px;
        }
        .top-bar {
            background: #fff;
            padding: 15px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .page-title {
            font-size: 1.5em;
            font-weight: bold;
            color: #2c3e50;
        }
        .user-info {
            display: flex;
            align-items: center;
        }
        .welcome {
            margin-right: 15px;
            color: #7f8c8d;
        }
        .content {
            flex: 1;
            padding: 25px;
            background: #f5f6fa;
            overflow-y: auto;
        }
        .logout-btn {
            color: #ecf0f1;
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 10px 15px;
            border-radius: 4px;
            transition: background 0.3s;
            background: #e74c3c;
        }
        .logout-btn:hover {
            background: #c0392b;
            text-decoration: none;
        }
        .logout-btn i {
            margin-right: 8px;
        }
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
                overflow: hidden;
            }
            .sidebar .menu-header,
            .sidebar .menu-item span,
            .sidebar .logo span,
            .user-info .welcome {
                display: none;
            }
            .main-content {
                margin-left: 70px;
            }
            .menu-item {
                justify-content: center;
            }
            .menu-item i {
                margin-right: 0;
                font-size: 1.2em;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar" id="sidebar">
        <a href="?page=dashboard" class="logo">
            <span><i class="fas fa-user-tie"></i> Staff Panel</span>
            <button id="sidebarToggle" style="background:none;border:none;color:#fff;float:right;font-size:1.2em;cursor:pointer;" title="Toggle Sidebar"><i class="fas fa-bars"></i></button>
        </a>
        <div class="menu">
            <div class="menu-section">
                <a href="?page=dashboard" class="menu-item <?php echo $current_page == 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </div>
            <div class="menu-section">
                <div class="menu-header">Attendance</div>
                <a href="?page=scan_fingerprint" class="menu-item <?php echo $current_page == 'scan_fingerprint' ? 'active' : ''; ?>">
                    <i class="fas fa-fingerprint"></i>
                    <span>Scan Fingerprint</span>
                </a>
                <a href="?page=view_attendance" class="menu-item <?php echo $current_page == 'view_attendance' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-check"></i>
                    <span>View Attendance</span>
                </a>
            </div>
            <div class="menu-section">
                <div class="menu-header">Students</div>
                <a href="?page=view_students" class="menu-item <?php echo $current_page == 'view_students' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span>View Students</span>
                </a>
            </div>
        </div>
        <div class="menu-footer">
            <a href="../logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <div class="page-title"><i class="fas fa-layer-group" style="color:#3498db;margin-right:10px;"></i><?php echo $page_title; ?></div>
            <div class="user-info">
                <span class="welcome">Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?></span>
                <a href="#" id="mobileLogout" class="logout-btn" style="display: none;">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
        <div class="content">
            <!-- Dynamic content will be loaded here -->
            <?php
            // Include content based on current page
            switch($current_page) {
                case 'dashboard':
                    echo '<h2>Welcome to Staff Dashboard</h2>';
                    echo '<p>Use the sidebar to scan fingerprints or view attendance records.</p>';
                    break;
                case 'scan_fingerprint':
                    include 'content_scan_fingerprint.php';
                    break;
                case 'view_attendance':
                    include 'content_view_student_attendance.php';
                    break;
                case 'view_students':
                    include 'content_view_students.php';
                    break;
                default:
                    echo '<div class="alert alert-warning">Page not found.</div>';
            }
            ?>
        </div>
    </div>

    <script>
        // Toggle sidebar
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('collapsed');
        });

        // Show/hide mobile logout button based on screen size
        function updateMobileView() {
            const welcomeText = document.querySelector('.welcome');
            const mobileLogout = document.getElementById('mobileLogout');
            if (window.innerWidth <= 768) {
                welcomeText.style.display = 'none';
                mobileLogout.style.display = 'flex';
            } else {
                welcomeText.style.display = 'block';
                mobileLogout.style.display = 'none';
            }
        }

        // Initial check
        updateMobileView();
        
        // Update on window resize
        window.addEventListener('resize', updateMobileView);

        // Handle mobile logout click
        document.getElementById('mobileLogout').addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = '../logout.php';
            }
        });
    </script>
</body>
</html>
<?php } else {
    header("Location: ../index.php");
    exit;
} ?>