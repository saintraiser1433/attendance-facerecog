<?php
// Fetch statistics
$total_students_sql = "SELECT COUNT(*) as count FROM students";
$total_tutors_sql = "SELECT COUNT(*) as count FROM tutors";
$active_tutors_sql = "SELECT COUNT(*) as count FROM tutors WHERE status = 'Active'";
$total_staff_sql = "SELECT COUNT(*) as count FROM users WHERE role = 'user'";

// Check if attendance table exists
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'attendance'");
$attendance_table_exists = mysqli_num_rows($table_check) > 0;

// Attendance statistics
$today = date('Y-m-d');
$week_start = date('Y-m-d', strtotime('monday this week'));
$month_start = date('Y-m-01');

// Initialize default values
$today_attendance = ['present_students' => 0, 'total_students' => 0];
$attendance_percentage = 0;
$weekly_data = [];
$monthly_data = [];

if ($attendance_table_exists) {
    // Today's attendance
    $today_attendance_sql = "SELECT 
        COUNT(DISTINCT student_id) as present_students,
        (SELECT COUNT(*) FROM students) as total_students
        FROM attendance 
        WHERE DATE(attendance_time) = '$today'";
    $today_result = mysqli_query($conn, $today_attendance_sql);
    if ($today_result) {
        $today_attendance = mysqli_fetch_assoc($today_result);
        $attendance_percentage = $today_attendance['total_students'] > 0 
            ? round(($today_attendance['present_students'] / $today_attendance['total_students']) * 100, 1)
            : 0;
    }

    // Weekly attendance data
    $week_days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    $current_day = strtotime('monday this week');

    for ($i = 0; $i < 7; $i++) {
        $day = date('Y-m-d', $current_day);
        $day_name = $week_days[$i];
        $sql = "SELECT COUNT(DISTINCT student_id) as count 
                FROM attendance 
                WHERE DATE(attendance_time) = '$day'";
        $result = mysqli_query($conn, $sql);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            $weekly_data[$day_name] = $row['count'] ?? 0;
        } else {
            $weekly_data[$day_name] = 0;
        }
        $current_day = strtotime('+1 day', $current_day);
    }

    // Monthly attendance data
    $current_month = date('m');
    $current_year = date('Y');
    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $current_month, $current_year);
    $month_name = date('F');

    for ($day = 1; $day <= $days_in_month; $day++) {
        $date = sprintf('%04d-%02d-%02d', $current_year, $current_month, $day);
        $sql = "SELECT COUNT(DISTINCT student_id) as count 
                FROM attendance 
                WHERE DATE(attendance_time) = '$date'";
        $result = mysqli_query($conn, $sql);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            $monthly_data[$day] = $row['count'] ?? 0;
        } else {
            $monthly_data[$day] = 0;
        }
    }
} else {
    // If attendance table doesn't exist, set empty data
    $week_days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    foreach ($week_days as $day_name) {
        $weekly_data[$day_name] = 0;
    }
    
    $current_month = date('m');
    $current_year = date('Y');
    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $current_month, $current_year);
    $month_name = date('F');
    
    for ($day = 1; $day <= $days_in_month; $day++) {
        $monthly_data[$day] = 0;
    }
}

$total_students = mysqli_fetch_assoc(mysqli_query($conn, $total_students_sql))['count'] ?? 0;
$total_tutors = mysqli_fetch_assoc(mysqli_query($conn, $total_tutors_sql))['count'] ?? 0;
$active_tutors = mysqli_fetch_assoc(mysqli_query($conn, $active_tutors_sql))['count'] ?? 0;
$total_staff = mysqli_fetch_assoc(mysqli_query($conn, $total_staff_sql))['count'] ?? 0;

// Fetch tutors with their assigned students for dashboard view
$tutors_overview_sql = "SELECT t.id, t.tutor_id, CONCAT(t.first_name, ' ', t.last_name) AS tutor_name, t.specialization, t.status
                        FROM tutors t
                        ORDER BY t.first_name, t.last_name";
$tutors_overview_result = mysqli_query($conn, $tutors_overview_sql);

$students_by_tutor = [];
if ($tutors_overview_result && mysqli_num_rows($tutors_overview_result) > 0) {
    while ($tutor_row = mysqli_fetch_assoc($tutors_overview_result)) {
        $tutor_id_key = (int)$tutor_row['id'];
        $students_by_tutor[$tutor_id_key] = [
            'info' => $tutor_row,
            'students' => []
        ];
    }

    if (!empty($students_by_tutor)) {
        $student_assignments_sql = "SELECT tsm.tutor_id,
                                           s.student_id,
                                           CONCAT(s.first_name, ' ', s.last_name) AS student_name,
                                           tsm.subject,
                                           tsm.status AS match_status,
                                           tsm.start_date
                                    FROM tutor_student_matching tsm
                                    LEFT JOIN students s ON tsm.student_id = s.id
                                    WHERE tsm.tutor_id IN (" . implode(',', array_keys($students_by_tutor)) . ")
                                    ORDER BY s.first_name, s.last_name";
        $student_assignments_result = mysqli_query($conn, $student_assignments_sql);

        if ($student_assignments_result && mysqli_num_rows($student_assignments_result) > 0) {
            while ($assignment_row = mysqli_fetch_assoc($student_assignments_result)) {
                $assignment_tutor_id = (int)$assignment_row['tutor_id'];
                if (isset($students_by_tutor[$assignment_tutor_id])) {
                    $students_by_tutor[$assignment_tutor_id]['students'][] = $assignment_row;
                }
            }
        }
    }
}
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
    .welcome-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #fff;
        border-radius: 8px;
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    .welcome-card h1 {
        margin: 0 0 10px 0;
        font-size: 2em;
    }
    .welcome-card p {
        margin: 0;
        opacity: 0.9;
    }
    .students-tutor-section {
        margin-top: 30px;
    }
    .students-tutor-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        color: #2c3e50;
    }
    .students-tutor-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
    }
    .tutor-card {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        padding: 18px;
        display: flex;
        flex-direction: column;
    }
    .tutor-card h4 {
        margin: 0;
        color: #2c3e50;
    }
    .tutor-meta {
        margin-top: 6px;
        color: #7f8c8d;
        font-size: 0.9em;
    }
    .badge {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 0.8em;
        font-weight: bold;
    }
    .badge-active {
        background: #d4edda;
        color: #155724;
    }
    .badge-inactive {
        background: #f8d7da;
        color: #721c24;
    }
    .badge-leave {
        background: #fff3cd;
        color: #856404;
    }
    .students-list {
        list-style: none;
        padding: 0;
        margin: 15px 0 0;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .student-item {
        background: #f8f9fa;
        border-radius: 6px;
        padding: 10px;
    }
    .student-item strong {
        display: block;
        color: #2c3e50;
    }
    .student-meta {
        margin-top: 4px;
        color: #7f8c8d;
        font-size: 0.85em;
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    .empty-state {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        padding: 30px;
        text-align: center;
        color: #7f8c8d;
        margin-top: 30px;
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

<?php if (!$attendance_table_exists): ?>
    <div style="background: #fff3cd; border: 2px solid #ffc107; border-radius: 8px; padding: 20px; margin-bottom: 20px; color: #856404;">
        <h4 style="margin: 0 0 10px 0;"><i class="fas fa-exclamation-triangle"></i> Attendance System Not Configured</h4>
        <p style="margin: 0;">The attendance tracking table has not been created yet. Attendance statistics will show zero until the attendance system is set up. Contact your system administrator to configure the attendance module.</p>
    </div>
<?php endif; ?>

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

<?php if (!empty($students_by_tutor)) : ?>
    <div class="students-tutor-section">
        <div class="students-tutor-header">
            <h3><i class="fas fa-users"></i> Students by Tutor Overview</h3>
            <a href="?page=students_by_tutor" style="font-size:0.9em;color:#3498db;text-decoration:none;">
                View full list <i class="fas fa-chevron-right"></i>
            </a>
        </div>
        <div class="students-tutor-grid">
            <?php foreach ($students_by_tutor as $tutor_bundle) :
                $info = $tutor_bundle['info'];
                $students = $tutor_bundle['students'];
                $status_class = 'badge-active';
                switch ($info['status']) {
                    case 'Inactive':
                        $status_class = 'badge-inactive';
                        break;
                    case 'On Leave':
                        $status_class = 'badge-leave';
                        break;
                }
            ?>
                <div class="tutor-card">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px;">
                        <div>
                            <h4><?php echo htmlspecialchars($info['tutor_name']); ?></h4>
                            <p class="tutor-meta">
                                ID: <?php echo htmlspecialchars($info['tutor_id']); ?>
                                <?php if (!empty($info['specialization'])) : ?>
                                    · <?php echo htmlspecialchars($info['specialization']); ?>
                                <?php endif; ?>
                            </p>
                        </div>
                        <span class="badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($info['status']); ?></span>
                    </div>

                    <?php if (!empty($students)) : ?>
                        <p style="margin:12px 0 0;font-weight:600;color:#2c3e50;">Assigned Students (<?php echo count($students); ?>)</p>
                        <ul class="students-list">
                            <?php foreach ($students as $student) : ?>
                                <li class="student-item">
                                    <strong><?php echo htmlspecialchars($student['student_name'] ?? 'Unknown Student'); ?></strong>
                                    <div class="student-meta">
                                        <span>ID: <?php echo htmlspecialchars($student['student_id'] ?? 'N/A'); ?></span>
                                        <?php if (!empty($student['subject'])) : ?>
                                            <span>Subject: <?php echo htmlspecialchars($student['subject']); ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($student['match_status'])) : ?>
                                            <span>Status: <?php echo htmlspecialchars($student['match_status']); ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($student['start_date'])) : ?>
                                            <span>Start Date: <?php echo htmlspecialchars($student['start_date']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else : ?>
                        <p style="margin:12px 0 0;color:#7f8c8d;">No students assigned yet.</p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php else : ?>
    <div class="empty-state">
        <i class="fas fa-user-graduate" style="font-size:2.5em;margin-bottom:10px;"></i>
        <h3 style="margin:0 0 5px;color:#2c3e50;">No tutor assignments yet</h3>
        <p>Assign students to tutors to see them directly on your dashboard.</p>
    </div>
<?php endif; ?>
