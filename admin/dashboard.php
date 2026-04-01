<?php 
   // Start output buffering immediately to catch any output
   ob_start();
   
   session_start();
   include "../db_conn.php";
   
   // Check for AJAX enrollment requests BEFORE any HTML output
   if (isset($_POST['ajax_enroll'])) {
       // Clean any output that might have been generated
       ob_clean();
       
       // Include the content file which will handle the AJAX request
       $current_page = isset($_GET['page']) ? $_GET['page'] : '';
       if ($current_page == 'enroll_student_fingerprint') {
           include 'content_enroll_student_fingerprint.php';
           exit(); // Exit after handling AJAX - don't output dashboard HTML
       } else if ($current_page == 'enroll_staff_fingerprint') {
           include 'content_enroll_staff_fingerprint.php';
           exit(); // Exit after handling AJAX - don't output dashboard HTML
       }
   }
   
   // Check for report generation BEFORE any HTML output
   if (isset($_GET['page']) && $_GET['page'] == 'generate_reports' && isset($_GET['generate'])) {
       include 'content_generate_reports.php';
       exit; // Stop execution after report is generated
   }
   
   if (isset($_SESSION['username']) && isset($_SESSION['id']) && $_SESSION['role'] == 'admin') {
       // Set the current page based on the 'page' parameter in the URL
       $current_page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
       $page_title = ucwords(str_replace('_', ' ', $current_page));
   ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Attendance System</title>
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
        }
        .logout-btn:hover {
            background: #e74c3c;
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
            <span><i class="fas fa-user-shield"></i> Admin Panel</span>
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
                <div class="menu-header">Manage Students</div>
                <a href="?page=manage_students" class="menu-item <?php echo $current_page == 'manage_students' ? 'active' : ''; ?>">
                    <i class="fas fa-list"></i>
                    <span>Student List</span>
                </a>
                <a href="?page=input_students" class="menu-item <?php echo $current_page == 'input_students' ? 'active' : ''; ?>">
                    <i class="fas fa-user-plus"></i>
                    <span>Input Students</span>
                </a>
                <a href="?page=manage_year_levels" class="menu-item <?php echo $current_page == 'manage_year_levels' ? 'active' : ''; ?>">
                    <i class="fas fa-layer-group"></i>
                    <span>Manage Year Levels</span>
                </a>
                <a href="?page=enroll_student_fingerprint" class="menu-item <?php echo $current_page == 'enroll_student_fingerprint' ? 'active' : ''; ?>">
                    <i class="fas fa-fingerprint"></i>
                    <span>Enroll Fingerprint</span>
                </a>
                <a href="?page=view_student_attendance" class="menu-item <?php echo $current_page == 'view_student_attendance' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-check"></i>
                    <span>View Attendance</span>
                </a>
                <a href="?page=students_by_tutor" class="menu-item <?php echo $current_page == 'students_by_tutor' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span>Students by Tutor</span>
                </a>
                <a href="?page=view_matching_tutor" class="menu-item <?php echo $current_page == 'view_matching_tutor' ? 'active' : ''; ?>">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <span>Matching Tutor</span>
                </a>
            </div>
            <div class="menu-section">
                <div class="menu-header">Manage Tutors & Staff</div>
                <a href="?page=manage_tutors" class="menu-item <?php echo $current_page == 'manage_tutors' ? 'active' : ''; ?>">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <span>Manage Tutors</span>
                </a>
                <a href="?page=add_tutor" class="menu-item <?php echo $current_page == 'add_tutor' ? 'active' : ''; ?>">
                    <i class="fas fa-user-plus"></i>
                    <span>Add Tutor</span>
                </a>
                <a href="?page=view_tutor_attendance" class="menu-item <?php echo $current_page == 'view_tutor_attendance' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-check"></i>
                    <span>Tutor Attendance</span>
                </a>
                <!-- Consolidated Staff Management under Tutor Section -->
                <a href="?page=input_staff" class="menu-item <?php echo $current_page == 'input_staff' ? 'active' : ''; ?>">
                    <i class="fas fa-user-tie"></i>
                    <span>Manage Staff</span>
                </a>
                <a href="?page=enroll_staff_fingerprint" class="menu-item <?php echo $current_page == 'enroll_staff_fingerprint' ? 'active' : ''; ?>">
                    <i class="fas fa-fingerprint"></i>
                    <span>Enroll Staff & Tutor Fingerprint</span>
                </a>
                <a href="?page=view_staff_attendance" class="menu-item <?php echo $current_page == 'view_staff_attendance' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-check"></i>
                    <span>View Staff Attendance</span>
                </a>
            </div>
            <div class="menu-section">
                <div class="menu-header">Attendance System</div>
                <a href="?page=biometric_attendance" class="menu-item <?php echo $current_page == 'biometric_attendance' ? 'active' : ''; ?>">
                    <i class="fas fa-fingerprint"></i>
                    <span>Biometric Attendance</span>
                </a>
                <a href="?page=attendance_monitoring" class="menu-item <?php echo $current_page == 'attendance_monitoring' ? 'active' : ''; ?>">
                    <i class="fas fa-bell"></i>
                    <span>Attendance Alerts</span>
                </a>
                <a href="?page=sms_settings" class="menu-item <?php echo $current_page == 'sms_settings' ? 'active' : ''; ?>">
                    <i class="fas fa-sms"></i>
                    <span>SMS Settings</span>
                </a>
            </div>
            <div class="menu-section">
                <div class="menu-header">AI Features</div>
                <a href="?page=tutor_matching_ai" class="menu-item <?php echo $current_page == 'tutor_matching_ai' ? 'active' : ''; ?>">
                    <i class="fas fa-brain"></i>
                    <span>AI Tutor Matching</span>
                </a>
            </div>
            <div class="menu-section">
                <div class="menu-header">Reports</div>
                <a href="?page=generate_reports" class="menu-item <?php echo $current_page == 'generate_reports' ? 'active' : ''; ?>">
                    <i class="fas fa-file-download"></i>
                    <span>Generate Reports</span>
                </a>
                <a href="?page=view_reports" class="menu-item <?php echo $current_page == 'view_reports' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar"></i>
                    <span>View Reports</span>
                </a>
                <a href="?page=export_reports" class="menu-item <?php echo $current_page == 'export_reports' ? 'active' : ''; ?>">
                    <i class="fas fa-file-export"></i>
                    <span>Export Reports</span>
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
                <img src="../img/admin-default.png" alt="Admin" style="width:32px;height:32px;border-radius:50%;margin-left:10px;">
            </div>
        </div>
        <div class="content">
            <?php
            // Include content based on current page
            switch($current_page) {
                case 'dashboard':
                    include 'content_dashboard.php';
                    break;
                case 'manage_students':
                    include 'content_manage_students.php';
                    break;
                case 'input_students':
                    include 'content_input_students_new.php';
                    break;
                case 'manage_year_levels':
                    include 'content_manage_year_levels.php';
                    break;
                case 'enroll_student_fingerprint':
                    include 'content_enroll_student_fingerprint.php';
                    break;
                case 'view_student_attendance':
                    include 'content_view_student_attendance.php';
                    break;
                case 'students_by_tutor':
                    include 'content_students_by_tutor.php';
                    break;
                case 'view_matching_tutor':
                    include 'content_view_matching_tutor.php';
                    break;
                case 'manage_tutors':
                    include 'content_manage_tutors.php';
                    break;
                case 'add_tutor':
                    include 'content_add_tutor_new.php';
                    break;
                case 'edit_tutor':
                    include 'content_edit_tutor.php';
                    break;
                case 'view_tutor_attendance':
                    include 'content_view_tutor_attendance.php';
                    break;
                case 'input_staff':
                    include 'staff_management.php';
                    break;
                case 'enroll_staff_fingerprint':
                    include 'content_enroll_staff_fingerprint.php';
                    break;
                case 'view_staff_attendance':
                    include 'content_view_staff_attendance.php';
                    break;
                case 'view_reports':
                    include 'content_view_reports.php';
                    break;
                case 'export_reports':
                    include 'content_export_reports.php';
                    break;
                case 'biometric_attendance':
                    include 'content_biometric_attendance.php';
                    break;
                case 'attendance_monitoring':
                    include 'content_attendance_monitoring.php';
                    break;
                case 'sms_settings':
                    include 'content_sms_settings.php';
                    break;
                case 'tutor_matching_ai':
                    include 'content_tutor_matching_ai.php';
                    break;
                case 'generate_reports':
                    include 'content_generate_reports.php';
                    break;
                default:
                    include 'content_dashboard.php';
            }
            ?>
        </div>
    </div>
    <script>
        // Sidebar toggle for mobile
        document.getElementById('sidebarToggle').onclick = function() {
            var sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('collapsed');
            if(sidebar.classList.contains('collapsed')) {
                sidebar.style.width = '70px';
                document.querySelector('.main-content').style.marginLeft = '70px';
            } else {
                sidebar.style.width = '250px';
                document.querySelector('.main-content').style.marginLeft = '250px';
            }
        };
    </script>
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