<?php
// View Tutor/Teacher Attendance
$success_message = '';
$error_message = '';

// Get filter parameters
$selected_tutor = isset($_GET['tutor_id']) ? intval($_GET['tutor_id']) : 0;
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01'); // First day of current month
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d'); // Today
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'All';

// Get all tutors for dropdown
$tutors_sql = "SELECT id, tutor_id, CONCAT(first_name, ' ', last_name) as name, specialization 
               FROM tutors 
               WHERE status = 'Active' 
               ORDER BY first_name, last_name";
$tutors_result = mysqli_query($conn, $tutors_sql);

// Build attendance query
$attendance_sql = "SELECT 
                    ta.id,
                    ta.tutor_id,
                    t.tutor_id as tutor_code,
                    CONCAT(t.first_name, ' ', t.last_name) as tutor_name,
                    t.specialization,
                    t.email,
                    t.phone,
                    ta.attendance_date,
                    ta.check_in_time,
                    ta.check_out_time,
                    ta.status,
                    ta.is_biometric_verified,
                    ta.fingerprint_match_score,
                    ta.notes,
                    TIMEDIFF(ta.check_out_time, ta.check_in_time) as hours_worked
                   FROM tutor_attendance ta
                   JOIN tutors t ON ta.tutor_id = t.id
                   WHERE ta.attendance_date BETWEEN ? AND ?";

$params = [$date_from, $date_to];
$types = "ss";

if ($selected_tutor > 0) {
    $attendance_sql .= " AND ta.tutor_id = ?";
    $params[] = $selected_tutor;
    $types .= "i";
}

if ($status_filter != 'All') {
    $attendance_sql .= " AND ta.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$attendance_sql .= " ORDER BY ta.attendance_date DESC, ta.check_in_time DESC";

$stmt = mysqli_prepare($conn, $attendance_sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$attendance_result = mysqli_stmt_get_result($stmt);

// Get statistics
$stats_sql = "SELECT 
                COUNT(DISTINCT tutor_id) as total_tutors,
                COUNT(CASE WHEN status = 'Present' THEN 1 END) as present_count,
                COUNT(CASE WHEN status = 'Absent' THEN 1 END) as absent_count,
                COUNT(CASE WHEN status = 'Late' THEN 1 END) as late_count,
                COUNT(CASE WHEN is_biometric_verified = 1 THEN 1 END) as verified_count,
                AVG(CASE WHEN fingerprint_match_score > 0 THEN fingerprint_match_score END) as avg_match_score
              FROM tutor_attendance
              WHERE attendance_date BETWEEN ? AND ?";

$stats_params = [$date_from, $date_to];
if ($selected_tutor > 0) {
    $stats_sql .= " AND tutor_id = ?";
    $stats_params[] = $selected_tutor;
}

$stats_stmt = mysqli_prepare($conn, $stats_sql);
if ($selected_tutor > 0) {
    mysqli_stmt_bind_param($stats_stmt, "ssi", ...$stats_params);
} else {
    mysqli_stmt_bind_param($stats_stmt, "ss", ...$stats_params);
}
mysqli_stmt_execute($stats_stmt);
$stats_result = mysqli_stmt_get_result($stats_stmt);
$stats = mysqli_fetch_assoc($stats_result);
?>

<style>
    .attendance-card {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        padding: 30px;
        margin-bottom: 30px;
    }
    .filter-section {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 30px;
    }
    .filter-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 15px;
    }
    .form-group {
        display: flex;
        flex-direction: column;
    }
    .form-group label {
        font-weight: 600;
        margin-bottom: 5px;
        color: #2c3e50;
    }
    .form-control {
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
    }
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    .stat-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px;
        border-radius: 8px;
        text-align: center;
    }
    .stat-card.present {
        background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
    }
    .stat-card.absent {
        background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
    }
    .stat-card.late {
        background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
    }
    .stat-card.verified {
        background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
    }
    .stat-number {
        font-size: 2.5em;
        font-weight: bold;
        margin: 10px 0;
    }
    .stat-label {
        font-size: 0.9em;
        opacity: 0.9;
    }
    .attendance-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: 8px;
        overflow: hidden;
    }
    .attendance-table thead {
        background: #34495e;
        color: white;
    }
    .attendance-table th,
    .attendance-table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #ecf0f1;
    }
    .attendance-table tbody tr:hover {
        background: #f8f9fa;
    }
    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 0.85em;
        font-weight: bold;
    }
    .status-present {
        background: #d4edda;
        color: #155724;
    }
    .status-absent {
        background: #f8d7da;
        color: #721c24;
    }
    .status-late {
        background: #fff3cd;
        color: #856404;
    }
    .status-excused {
        background: #d1ecf1;
        color: #0c5460;
    }
    .verified-badge {
        background: #d4edda;
        color: #155724;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 0.8em;
    }
    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        transition: all 0.3s;
        text-decoration: none;
        display: inline-block;
    }
    .btn-primary {
        background: #3498db;
        color: #fff;
    }
    .btn-success {
        background: #27ae60;
        color: #fff;
    }
    .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
    }
    .no-data {
        text-align: center;
        padding: 40px;
        color: #7f8c8d;
    }
    .no-data i {
        font-size: 3em;
        margin-bottom: 15px;
        color: #bdc3c7;
    }
</style>

<div class="attendance-card">
    <h2><i class="fas fa-chalkboard-teacher"></i> Tutor/Teacher Attendance Records</h2>
    <p>View and monitor tutor attendance with detailed records and statistics</p>

    <!-- Filter Section -->
    <div class="filter-section">
        <h4><i class="fas fa-filter"></i> Filter Attendance</h4>
        <form method="GET" action="">
            <input type="hidden" name="page" value="view_tutor_attendance">
            <div class="filter-row">
                <div class="form-group">
                    <label>Select Tutor</label>
                    <select name="tutor_id" class="form-control">
                        <option value="0">All Tutors</option>
                        <?php 
                        mysqli_data_seek($tutors_result, 0);
                        while ($tutor = mysqli_fetch_assoc($tutors_result)): 
                        ?>
                            <option value="<?php echo $tutor['id']; ?>" <?php echo $selected_tutor == $tutor['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($tutor['name']); ?> 
                                (<?php echo htmlspecialchars($tutor['tutor_id']); ?>) - 
                                <?php echo htmlspecialchars($tutor['specialization']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Date From</label>
                    <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
                </div>
                <div class="form-group">
                    <label>Date To</label>
                    <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="All" <?php echo $status_filter == 'All' ? 'selected' : ''; ?>>All Status</option>
                        <option value="Present" <?php echo $status_filter == 'Present' ? 'selected' : ''; ?>>Present</option>
                        <option value="Absent" <?php echo $status_filter == 'Absent' ? 'selected' : ''; ?>>Absent</option>
                        <option value="Late" <?php echo $status_filter == 'Late' ? 'selected' : ''; ?>>Late</option>
                        <option value="Excused" <?php echo $status_filter == 'Excused' ? 'selected' : ''; ?>>Excused</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Apply Filters
            </button>
            <a href="?page=view_tutor_attendance" class="btn btn-success">
                <i class="fas fa-redo"></i> Reset
            </a>
        </form>
    </div>

    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <i class="fas fa-users" style="font-size:2em;"></i>
            <div class="stat-number"><?php echo $stats['total_tutors']; ?></div>
            <div class="stat-label">Total Tutors</div>
        </div>
        <div class="stat-card present">
            <i class="fas fa-check-circle" style="font-size:2em;"></i>
            <div class="stat-number"><?php echo $stats['present_count']; ?></div>
            <div class="stat-label">Present</div>
        </div>
        <div class="stat-card absent">
            <i class="fas fa-times-circle" style="font-size:2em;"></i>
            <div class="stat-number"><?php echo $stats['absent_count']; ?></div>
            <div class="stat-label">Absent</div>
        </div>
        <div class="stat-card late">
            <i class="fas fa-clock" style="font-size:2em;"></i>
            <div class="stat-number"><?php echo $stats['late_count']; ?></div>
            <div class="stat-label">Late</div>
        </div>
        <div class="stat-card verified">
            <i class="fas fa-fingerprint" style="font-size:2em;"></i>
            <div class="stat-number"><?php echo $stats['verified_count']; ?></div>
            <div class="stat-label">Verified</div>
        </div>
        <div class="stat-card">
            <i class="fas fa-percentage" style="font-size:2em;"></i>
            <div class="stat-number"><?php echo number_format($stats['avg_match_score'] ?? 0, 1); ?>%</div>
            <div class="stat-label">Avg Match Score</div>
        </div>
    </div>

    <!-- Attendance Table -->
    <h3><i class="fas fa-table"></i> Attendance Records</h3>
    <div style="overflow-x:auto;">
        <?php if (mysqli_num_rows($attendance_result) > 0): ?>
            <table class="attendance-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Tutor ID</th>
                        <th>Tutor Name</th>
                        <th>Specialization</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                        <th>Hours</th>
                        <th>Status</th>
                        <th>Verified</th>
                        <th>Match Score</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($attendance_result)): ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($row['attendance_date'])); ?></td>
                            <td><strong><?php echo htmlspecialchars($row['tutor_code']); ?></strong></td>
                            <td>
                                <div style="font-weight:600;"><?php echo htmlspecialchars($row['tutor_name']); ?></div>
                                <div style="font-size:0.85em;color:#7f8c8d;">
                                    <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($row['email']); ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($row['specialization']); ?></td>
                            <td>
                                <?php if ($row['check_in_time']): ?>
                                    <i class="fas fa-sign-in-alt" style="color:#27ae60;"></i>
                                    <?php echo date('g:i A', strtotime($row['check_in_time'])); ?>
                                <?php else: ?>
                                    <span style="color:#95a5a6;">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['check_out_time']): ?>
                                    <i class="fas fa-sign-out-alt" style="color:#e74c3c;"></i>
                                    <?php echo date('g:i A', strtotime($row['check_out_time'])); ?>
                                <?php else: ?>
                                    <span style="color:#95a5a6;">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['hours_worked']): ?>
                                    <strong><?php echo $row['hours_worked']; ?></strong>
                                <?php else: ?>
                                    <span style="color:#95a5a6;">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($row['status']); ?>">
                                    <?php echo $row['status']; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($row['is_biometric_verified']): ?>
                                    <span class="verified-badge">
                                        <i class="fas fa-check"></i> Verified
                                    </span>
                                <?php else: ?>
                                    <span style="color:#95a5a6;">Manual</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['fingerprint_match_score'] > 0): ?>
                                    <strong style="color:#27ae60;"><?php echo number_format($row['fingerprint_match_score'], 1); ?>%</strong>
                                <?php else: ?>
                                    <span style="color:#95a5a6;">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['notes']): ?>
                                    <span style="font-size:0.9em;"><?php echo htmlspecialchars($row['notes']); ?></span>
                                <?php else: ?>
                                    <span style="color:#95a5a6;">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-data">
                <i class="fas fa-calendar-times"></i>
                <p>No attendance records found for the selected filters.</p>
                <p style="font-size:0.9em;color:#95a5a6;">Try adjusting your date range or tutor selection.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Export Options -->
<div class="attendance-card">
    <h3><i class="fas fa-download"></i> Export Options</h3>
    <p>Download attendance records in your preferred format</p>
    <div style="display:flex;gap:15px;margin-top:20px;">
        <a href="export_tutor_attendance.php?format=excel&tutor_id=<?php echo $selected_tutor; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>&status=<?php echo $status_filter; ?>" class="btn btn-success">
            <i class="fas fa-file-excel"></i> Export to Excel
        </a>
        <a href="export_tutor_attendance.php?format=pdf&tutor_id=<?php echo $selected_tutor; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>&status=<?php echo $status_filter; ?>" class="btn btn-primary">
            <i class="fas fa-file-pdf"></i> Export to PDF
        </a>
    </div>
</div>

<script>
// Auto-submit form on date change for better UX
document.querySelectorAll('.form-control').forEach(function(element) {
    if (element.type === 'date') {
        element.addEventListener('change', function() {
            // Optional: auto-submit on date change
            // this.form.submit();
        });
    }
});
</script>
