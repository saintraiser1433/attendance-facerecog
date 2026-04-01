<?php
// Fetch statistics
$total_students_sql = "SELECT COUNT(*) as count FROM students";
$total_tutors_sql = "SELECT COUNT(*) as count FROM tutors";
$active_tutors_sql = "SELECT COUNT(*) as count FROM tutors WHERE status = 'Active'";
$total_staff_sql = "SELECT COUNT(*) as count FROM users WHERE role = 'user'";

// Attendance statistics
$today = date('Y-m-d');
$week_start = date('Y-m-d', strtotime('monday this week'));
$month_start = date('Y-m-01');

// Today's attendance
$today_attendance_sql = "SELECT 
    COUNT(DISTINCT student_id) as present_students,
    (SELECT COUNT(*) FROM students) as total_students
    FROM attendance 
    WHERE DATE(attendance_time) = '$today'";
$today_attendance = mysqli_fetch_assoc(mysqli_query($conn, $today_attendance_sql));
$attendance_percentage = $today_attendance['total_students'] > 0 
    ? round(($today_attendance['present_students'] / $today_attendance['total_students']) * 100, 1)
    : 0;

// Weekly attendance data
$weekly_data = [];
$week_days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
$current_day = strtotime('monday this week');

for ($i = 0; $i < 7; $i++) {
    $day = date('Y-m-d', $current_day);
    $day_name = $week_days[$i];
    $sql = "SELECT COUNT(DISTINCT student_id) as count 
            FROM attendance 
            WHERE DATE(attendance_time) = '$day'";
    $result = mysqli_fetch_assoc(mysqli_query($conn, $sql));
    $weekly_data[$day_name] = $result['count'] ?? 0;
    $current_day = strtotime('+1 day', $current_day);
}

// Monthly attendance data
$monthly_data = [];
$current_month = date('m');
$current_year = date('Y');
$days_in_month = cal_days_in_month(CAL_GREGORIAN, $current_month, $current_year);
$month_name = date('F');

for ($day = 1; $day <= $days_in_month; $day++) {
    $date = sprintf('%04d-%02d-%02d', $current_year, $current_month, $day);
    $sql = "SELECT COUNT(DISTINCT student_id) as count 
            FROM attendance 
            WHERE DATE(attendance_time) = '$date'";
    $result = mysqli_fetch_assoc(mysqli_query($conn, $sql));
    $monthly_data[$day] = $result['count'] ?? 0;
}

$total_students = mysqli_fetch_assoc(mysqli_query($conn, $total_students_sql))['count'] ?? 0;
$total_tutors = mysqli_fetch_assoc(mysqli_query($conn, $total_tutors_sql))['count'] ?? 0;
$active_tutors = mysqli_fetch_assoc(mysqli_query($conn, $active_tutors_sql))['count'] ?? 0;
$total_staff = mysqli_fetch_assoc(mysqli_query($conn, $total_staff_sql))['count'] ?? 0;
?>

<!-- Add Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    .stat-card {
        background: #fff;
        border-radius: 8px;
        padding: 25px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        display: flex;
        align-items: center;
        transition: transform 0.3s;
    }
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    .stat-icon {
        font-size: 2.5em;
        margin-right: 20px;
        width: 70px;
        height: 70px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
    }
    .stat-icon.blue { background: #e3f2fd; color: #2196f3; }
    .stat-icon.green { background: #e8f5e9; color: #4caf50; }
    .stat-icon.orange { background: #fff3e0; color: #ff9800; }
    .stat-icon.purple { background: #f3e5f5; color: #9c27b0; }
    .stat-details { flex: 1; }
    .stat-number { 
        font-size: 2em;
        font-weight: bold;
        color: #2c3e50;
        margin: 0;
    }
    .stat-label {
        color: #7f8c8d;
        font-size: 0.9em;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .chart-container {
        background: #fff;
        border-radius: 8px;
        padding: 25px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }
    .chart-container h3 {
        margin-top: 0;
        color: #2c3e50;
        border-bottom: 2px solid #f0f0f0;
        padding-bottom: 10px;
        margin-bottom: 20px;
    }
    .chart-wrapper {
        position: relative;
        height: 300px;
        width: 100%;
    }
    .attendance-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
</style>

<div class="welcome-card">
    <h1><i class="fas fa-user-shield"></i> Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h1>
    <p>Here's an overview of your attendance system</p>
</div>

<div class="dashboard-grid">
    <div class="stat-card">
        <div class="stat-icon blue">
            <i class="fas fa-user-graduate"></i>
        </div>
        <div class="stat-details">
            <p class="stat-number"><?php echo $total_students; ?></p>
            <p class="stat-label">Total Students</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon green">
            <i class="fas fa-chalkboard-teacher"></i>
        </div>
        <div class="stat-details">
            <p class="stat-number"><?php echo $active_tutors; ?>/<?php echo $total_tutors; ?></p>
            <p class="stat-label">Active/Total Tutors</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon orange">
            <i class="fas fa-user-check"></i>
        </div>
        <div class="stat-details">
            <p class="stat-number"><?php echo $today_attendance['present_students'] ?? 0; ?>/<?php echo $today_attendance['total_students'] ?? 0; ?></p>
            <p class="stat-label">Today's Attendance</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon purple">
            <i class="fas fa-percentage"></i>
        </div>
        <div class="stat-details">
            <p class="stat-number"><?php echo $attendance_percentage; ?>%</p>
            <p class="stat-label">Attendance Rate Today</p>
        </div>
    </div>
</div>

<div class="chart-container">
    <h3><i class="fas fa-chart-line"></i> Weekly Attendance Overview</h3>
    <div class="chart-wrapper">
        <canvas id="weeklyChart"></canvas>
    </div>
</div>

<div class="chart-container">
    <h3><i class="fas fa-calendar-alt"></i> Monthly Attendance Trend - <?php echo $month_name; ?></h3>
    <div class="chart-wrapper">
        <canvas id="monthlyChart"></canvas>
    </div>
</div>

<script>
// Weekly Attendance Chart
const weeklyCtx = document.getElementById('weeklyChart').getContext('2d');
const weeklyChart = new Chart(weeklyCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_keys($weekly_data)); ?>,
        datasets: [{
            label: 'Number of Students',
            data: <?php echo json_encode(array_values($weekly_data)); ?>,
            backgroundColor: 'rgba(54, 162, 235, 0.5)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Number of Students'
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'Day of Week'
                }
            }
        },
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.parsed.y + ' students';
                    }
                }
            }
        }
    }
});

// Monthly Attendance Chart
const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
const monthlyChart = new Chart(monthlyCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(range(1, count($monthly_data))); ?>,
        datasets: [{
            label: 'Daily Attendance',
            data: <?php echo json_encode(array_values($monthly_data)); ?>,
            fill: true,
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            borderColor: 'rgba(75, 192, 192, 1)',
            tension: 0.3,
            pointBackgroundColor: 'rgba(75, 192, 192, 1)',
            pointBorderColor: '#fff',
            pointHoverRadius: 5,
            pointHoverBackgroundColor: 'rgba(75, 192, 192, 1)',
            pointHoverBorderColor: 'rgba(255,255,255,1)',
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Number of Students'
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'Day of Month'
                },
                ticks: {
                    callback: function(value) {
                        return value % 5 === 0 ? value : ''; // Show every 5th day
                    }
                }
            }
        },
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.parsed.y + ' students';
                    }
                }
            }
        }
    }
});
</script>
