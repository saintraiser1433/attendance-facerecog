<?php
session_start();
include "../db_conn.php";

// Check if tutor is logged in
if (!isset($_SESSION['tutor_id'])) {
    header("Location: ../tutor_login.php");
    exit();
}

// Get tutor details
$tutor_id = $_SESSION['tutor_id'];
$tutor_sql = "SELECT * FROM tutors WHERE id = ?";
$stmt = $conn->prepare($tutor_sql);
$stmt->bind_param("i", $tutor_id);
$stmt->execute();
$tutor = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get today's date
$today = date('Y-m-d');

// Get today's classes
$today_classes_sql = "SELECT c.*, s.name as subject_name, r.room_number 
                     FROM classes c 
                     JOIN subjects s ON c.subject_id = s.id 
                     LEFT JOIN rooms r ON c.room_id = r.id 
                     WHERE c.tutor_id = ? AND c.class_date = ?
                     ORDER BY c.start_time";
$stmt = $conn->prepare($today_classes_sql);
$stmt->bind_param("is", $tutor_id, $today);
$stmt->execute();
$today_classes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get upcoming classes (next 7 days)
$upcoming_start = date('Y-m-d', strtotime('+1 day'));
$upcoming_end = date('Y-m-d', strtotime('+7 days'));
$upcoming_sql = "SELECT c.*, s.name as subject_name, r.room_number 
                FROM classes c 
                JOIN subjects s ON c.subject_id = s.id 
                LEFT JOIN rooms r ON c.room_id = r.id 
                WHERE c.tutor_id = ? AND c.class_date BETWEEN ? AND ?
                ORDER BY c.class_date, c.start_time";
$stmt = $conn->prepare($upcoming_sql);
$stmt->bind_param("iss", $tutor_id, $upcoming_start, $upcoming_end);
$stmt->execute();
$upcoming_classes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get attendance statistics
$attendance_sql = "SELECT 
    COUNT(*) as total_classes,
    SUM(CASE WHEN c.status = 'completed' THEN 1 ELSE 0 END) as completed_classes,
    (SELECT COUNT(DISTINCT student_id) FROM class_attendance ca 
     JOIN classes cl ON ca.class_id = cl.id 
     WHERE cl.tutor_id = ? AND ca.status = 'present') as students_attended
FROM classes c 
WHERE c.tutor_id = ?";
$stmt = $conn->prepare($attendance_sql);
$stmt->bind_param("ii", $tutor_id, $tutor_id);
$stmt->execute();
$attendance_stats = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tutor Dashboard - Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #f5576c;
            --secondary-color: #f093fb;
            --dark-color: #2c3e50;
            --light-color: #f8f9fa;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: #333;
        }
        .sidebar {
            background: var(--dark-color);
            color: white;
            min-height: 100vh;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            margin: 5px 0;
            border-radius: 5px;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        .sidebar .nav-link i {
            width: 24px;
            text-align: center;
            margin-right: 10px;
        }
        .main-content {
            padding: 20px;
        }
        .welcome-card {
            background: linear-gradient(135deg, var(--secondary-color) 0%, var(--primary-color) 100%);
            color: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark-color);
            margin: 10px 0;
        }
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        .card-header {
            background: white;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            font-weight: 600;
            padding: 15px 20px;
        }
        .list-group-item {
            border-left: none;
            border-right: none;
            padding: 15px 20px;
        }
        .badge-success {
            background-color: var(--success-color);
        }
        .badge-warning {
            background-color: var(--warning-color);
        }
        .badge-danger {
            background-color: var(--danger-color);
        }
        .user-menu {
            background: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
        }
        .user-info h5 {
            margin: 0;
            font-weight: 600;
        }
        .user-info p {
            margin: 5px 0 0;
            color: #6c757d;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 px-0 sidebar">
                <div class="text-center py-4">
                    <h4>Attendance System</h4>
                    <p class="text-muted small">Tutor Portal</p>
                </div>
                <hr class="bg-secondary">
                <ul class="nav flex-column px-3">
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link active">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="classes.php" class="nav-link">
                            <i class="fas fa-chalkboard"></i> My Classes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="attendance.php" class="nav-link">
                            <i class="fas fa-clipboard-check"></i> Take Attendance
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="students.php" class="nav-link">
                            <i class="fas fa-users"></i> Students
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="reports.php" class="nav-link">
                            <i class="fas fa-chart-bar"></i> Reports
                        </a>
                    </li>
                    <li class="nav-item mt-4">
                        <a href="profile.php" class="nav-link">
                            <i class="fas fa-user-cog"></i> Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="../logout.php" class="nav-link text-danger">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 main-content">
                <!-- Top Bar -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="mb-0">Tutor Dashboard</h2>
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="far fa-bell text-muted" style="font-size: 1.2rem;"></i>
                        </div>
                        <div class="dropdown">
                            <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <img src="<?php echo !empty($tutor['profile_picture']) ? '../' . $tutor['profile_picture'] : 'https://ui-avatars.com/api/?name=' . urlencode($tutor['first_name'] . ' ' . $tutor['last_name']) . '&background=random'; ?>" alt="Profile" class="rounded-circle" width="40" height="40">
                                <span class="ms-2 d-none d-md-inline"><?php echo htmlspecialchars($tutor['first_name']); ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                                <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Welcome Card -->
                <div class="welcome-card">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2>Welcome back, <?php echo htmlspecialchars($tutor['first_name']); ?>!</h2>
                            <p class="mb-0">You have <?php echo count($today_classes); ?> classes scheduled for today.</p>
                        </div>
                        <div class="col-md-4 text-end d-none d-md-block">
                            <i class="fas fa-chalkboard-teacher" style="font-size: 5rem; opacity: 0.3;"></i>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-light p-3 me-3" style="background-color: #e3f2fd !important;">
                                    <i class="fas fa-chalkboard-teacher text-primary" style="font-size: 1.5rem;"></i>
                                </div>
                                <div>
                                    <div class="stat-number"><?php echo $attendance_stats['completed_classes'] ?? 0; ?>/<?php echo $attendance_stats['total_classes'] ?? 0; ?></div>
                                    <div class="stat-label">Classes Completed</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-light p-3 me-3" style="background-color: #e8f5e9 !important;">
                                    <i class="fas fa-user-check text-success" style="font-size: 1.5rem;"></i>
                                </div>
                                <div>
                                    <div class="stat-number"><?php echo $attendance_stats['students_attended'] ?? 0; ?></div>
                                    <div class="stat-label">Students Attended</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-light p-3 me-3" style="background-color: #fff3e0 !important;">
                                    <i class="fas fa-calendar-check text-warning" style="font-size: 1.5rem;"></i>
                                </div>
                                <div>
                                    <div class="stat-number"><?php echo count($upcoming_classes); ?></div>
                                    <div class="stat-label">Upcoming Classes</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Today's Classes -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span><i class="far fa-calendar-alt me-2"></i>Today's Classes</span>
                                <span class="badge bg-primary"><?php echo count($today_classes); ?></span>
                            </div>
                            <?php if (count($today_classes) > 0): ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($today_classes as $class): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($class['subject_name']); ?></h6>
                                                    <small class="text-muted">
                                                        <i class="far fa-clock me-1"></i>
                                                        <?php echo date('h:i A', strtotime($class['start_time'])) . ' - ' . date('h:i A', strtotime($class['end_time'])); ?>
                                                        <?php if (!empty($class['room_number'])): ?>
                                                            <span class="ms-2">
                                                                <i class="fas fa-door-open me-1"></i>Room <?php echo htmlspecialchars($class['room_number']); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                                <a href="attendance.php?class_id=<?php echo $class['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-clipboard-check me-1"></i> Take Attendance
                                                </a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="card-body text-center py-5">
                                    <i class="far fa-calendar-times text-muted mb-3" style="font-size: 2rem;"></i>
                                    <p class="text-muted mb-0">No classes scheduled for today</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Upcoming Classes -->
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span><i class="far fa-calendar-plus me-2"></i>Upcoming Classes</span>
                                <span class="badge bg-primary"><?php echo count($upcoming_classes); ?></span>
                            </div>
                            <?php if (count($upcoming_classes) > 0): ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($upcoming_classes as $class): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($class['subject_name']); ?></h6>
                                                    <div class="d-flex flex-wrap">
                                                        <small class="text-muted me-3">
                                                            <i class="far fa-calendar me-1"></i>
                                                            <?php echo date('M j, Y', strtotime($class['class_date'])); ?>
                                                        </small>
                                                        <small class="text-muted">
                                                            <i class="far fa-clock me-1"></i>
                                                            <?php echo date('h:i A', strtotime($class['start_time'])) . ' - ' . date('h:i A', strtotime($class['end_time'])); ?>
                                                        </small>
                                                    </div>
                                                    <?php if (!empty($class['room_number'])): ?>
                                                        <small class="text-muted">
                                                            <i class="fas fa-door-open me-1"></i>Room <?php echo htmlspecialchars($class['room_number']); ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="card-body text-center py-5">
                                    <i class="far fa-calendar-plus text-muted mb-3" style="font-size: 2rem;"></i>
                                    <p class="text-muted mb-0">No upcoming classes in the next 7 days</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Attendance Overview Chart -->
                <div class="card mt-4">
                    <div class="card-header">
                        <i class="fas fa-chart-line me-2"></i>Attendance Overview (Last 30 Days)
                    </div>
                    <div class="card-body">
                        <canvas id="attendanceChart" height="100"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Sample data for the chart (replace with actual data from your database)
    const ctx = document.getElementById('attendanceChart').getContext('2d');
    const attendanceChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: Array.from({length: 30}, (_, i) => {
                const d = new Date();
                d.setDate(d.getDate() - 29 + i);
                return d.toLocaleDateString('en-US', {month: 'short', day: 'numeric'});
            }),
            datasets: [{
                label: 'Attendance Rate',
                data: Array.from({length: 30}, () => Math.floor(Math.random() * 30) + 70), // Random data between 70-100
                borderColor: '#f5576c',
                backgroundColor: 'rgba(245, 87, 108, 0.1)',
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Attendance: ' + context.parsed.y + '%';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            }
        }
    });
    </script>
</body>
</html>
