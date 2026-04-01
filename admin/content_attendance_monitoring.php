<?php
// Attendance Monitoring and Alert System
$success_message = '';
$error_message = '';

// Check and generate alerts for students with frequent absences
function checkAttendanceAlerts($conn) {
    $threshold_days = 30; // Check last 30 days
    $absence_threshold = 5; // Alert if more than 5 absences
    
    $sql = "SELECT s.id, s.student_id, CONCAT(s.first_name, ' ', s.last_name) as name,
                   COUNT(CASE WHEN sa.status = 'Absent' THEN 1 END) as absence_count,
                   COUNT(CASE WHEN sa.status = 'Late' THEN 1 END) as late_count,
                   COUNT(CASE WHEN sa.status = 'Present' THEN 1 END) as present_count
            FROM students s
            LEFT JOIN student_attendance sa ON s.id = sa.student_id 
                AND sa.attendance_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            WHERE s.status = 'Active'
            GROUP BY s.id
            HAVING absence_count >= ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $threshold_days, $absence_threshold);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $alerts_generated = 0;
    
    while ($row = mysqli_fetch_assoc($result)) {
        $severity = 'Medium';
        if ($row['absence_count'] >= 10) {
            $severity = 'Critical';
        } elseif ($row['absence_count'] >= 7) {
            $severity = 'High';
        }
        
        $alert_message = "Student {$row['name']} ({$row['student_id']}) has been absent {$row['absence_count']} times in the last {$threshold_days} days.";
        
        // Check if alert already exists for this student
        $check_sql = "SELECT id FROM attendance_alerts 
                      WHERE student_id = ? 
                      AND alert_type = 'Frequent Absence' 
                      AND DATE(notified_at) = CURDATE()";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "i", $row['id']);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        
        if (mysqli_num_rows($check_result) == 0) {
            // Create new alert
            $insert_sql = "INSERT INTO attendance_alerts (student_id, alert_type, absence_count, alert_message, severity) 
                          VALUES (?, 'Frequent Absence', ?, ?, ?)";
            $insert_stmt = mysqli_prepare($conn, $insert_sql);
            mysqli_stmt_bind_param($insert_stmt, "iiss", $row['id'], $row['absence_count'], $alert_message, $severity);
            mysqli_stmt_execute($insert_stmt);
            mysqli_stmt_close($insert_stmt);
            
            // Create notification for admin
            $notif_sql = "INSERT INTO notifications (user_id, notification_type, title, message, priority) 
                         VALUES (1, 'Attendance Alert', 'Frequent Absence Alert', ?, ?)";
            $notif_stmt = mysqli_prepare($conn, $notif_sql);
            $priority = ($severity == 'Critical') ? 'Urgent' : (($severity == 'High') ? 'High' : 'Normal');
            mysqli_stmt_bind_param($notif_stmt, "ss", $alert_message, $priority);
            mysqli_stmt_execute($notif_stmt);
            mysqli_stmt_close($notif_stmt);
            
            $alerts_generated++;
        }
        mysqli_stmt_close($check_stmt);
    }
    
    mysqli_stmt_close($stmt);
    return $alerts_generated;
}

// Handle manual alert check
if (isset($_POST['check_alerts'])) {
    $count = checkAttendanceAlerts($conn);
    $success_message = "Attendance check completed. {$count} new alerts generated.";
}

// Mark alert as read
if (isset($_POST['mark_read'])) {
    $alert_id = intval($_POST['alert_id']);
    $update_sql = "UPDATE attendance_alerts SET is_read = 1 WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($stmt, "i", $alert_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

// Get all alerts
$alerts_sql = "SELECT aa.*, s.student_id, CONCAT(s.first_name, ' ', s.last_name) as student_name, s.email
               FROM attendance_alerts aa
               JOIN students s ON aa.student_id = s.id
               ORDER BY aa.is_read ASC, aa.severity DESC, aa.notified_at DESC";
$alerts_result = mysqli_query($conn, $alerts_sql);

// Get statistics
$stats_sql = "SELECT 
                COUNT(CASE WHEN is_read = 0 THEN 1 END) as unread_count,
                COUNT(CASE WHEN severity = 'Critical' THEN 1 END) as critical_count,
                COUNT(CASE WHEN severity = 'High' THEN 1 END) as high_count,
                COUNT(*) as total_count
              FROM attendance_alerts
              WHERE DATE(notified_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
$stats_result = mysqli_query($conn, $stats_sql);
$stats = mysqli_fetch_assoc($stats_result);
?>

<style>
    .monitoring-card {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        padding: 30px;
        margin-bottom: 30px;
    }
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
    .stat-card.critical {
        background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
    }
    .stat-card.high {
        background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
    }
    .stat-card.unread {
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
    .alert-item {
        background: #fff;
        border-left: 4px solid #3498db;
        padding: 20px;
        margin-bottom: 15px;
        border-radius: 4px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: all 0.3s;
    }
    .alert-item:hover {
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    .alert-item.critical {
        border-left-color: #e74c3c;
        background: #fff5f5;
    }
    .alert-item.high {
        border-left-color: #f39c12;
        background: #fffbf0;
    }
    .alert-item.medium {
        border-left-color: #3498db;
    }
    .alert-item.read {
        opacity: 0.6;
    }
    .severity-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 0.85em;
        font-weight: bold;
    }
    .severity-critical {
        background: #e74c3c;
        color: white;
    }
    .severity-high {
        background: #f39c12;
        color: white;
    }
    .severity-medium {
        background: #3498db;
        color: white;
    }
    .severity-low {
        background: #95a5a6;
        color: white;
    }
    .alert {
        padding: 12px 20px;
        margin-bottom: 20px;
        border-radius: 4px;
        font-weight: 500;
    }
    .alert-success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
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
</style>

<div class="monitoring-card">
    <h2><i class="fas fa-bell"></i> Attendance Monitoring & Alerts</h2>
    <p>Automated monitoring system that tracks student attendance patterns and generates alerts.</p>
    <p style="color:#555;font-size:14px;"><i class="fas fa-sms"></i> While this page is open, the system checks every <strong>2 minutes</strong> for students marked <strong>Absent</strong> on both yesterday and today, then sends one parent SMS per student (via <a href="?page=sms_settings">SMS Settings</a>). Emergency contact number is used first, then the student phone.</p>
    <pre id="parent-sms-poll-log" style="display:none;margin-top:12px;padding:10px;background:#f8f9fa;border-radius:4px;font-size:12px;white-space:pre-wrap;"></pre>

    <?php if ($success_message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
        </div>
    <?php endif; ?>

    <div style="margin-bottom:30px;">
        <form method="POST" action="" style="display:inline;">
            <button type="submit" name="check_alerts" class="btn btn-primary">
                <i class="fas fa-sync-alt"></i> Run Attendance Check
            </button>
        </form>
        <span style="margin-left:15px;color:#7f8c8d;">
            <i class="fas fa-info-circle"></i> Automatically checks for students with 5+ absences in last 30 days
        </span>
    </div>

    <div class="stats-grid">
        <div class="stat-card unread">
            <i class="fas fa-envelope" style="font-size:2em;"></i>
            <div class="stat-number"><?php echo $stats['unread_count']; ?></div>
            <div class="stat-label">Unread Alerts</div>
        </div>
        
        <div class="stat-card critical">
            <i class="fas fa-exclamation-triangle" style="font-size:2em;"></i>
            <div class="stat-number"><?php echo $stats['critical_count']; ?></div>
            <div class="stat-label">Critical Alerts</div>
        </div>
        
        <div class="stat-card high">
            <i class="fas fa-exclamation-circle" style="font-size:2em;"></i>
            <div class="stat-number"><?php echo $stats['high_count']; ?></div>
            <div class="stat-label">High Priority</div>
        </div>
        
        <div class="stat-card">
            <i class="fas fa-list" style="font-size:2em;"></i>
            <div class="stat-number"><?php echo $stats['total_count']; ?></div>
            <div class="stat-label">Total (30 days)</div>
        </div>
    </div>
</div>

<div class="monitoring-card">
    <h3><i class="fas fa-list-alt"></i> Alert History</h3>
    
    <?php if (mysqli_num_rows($alerts_result) > 0): ?>
        <?php while ($alert = mysqli_fetch_assoc($alerts_result)): ?>
            <div class="alert-item <?php echo strtolower($alert['severity']); ?> <?php echo $alert['is_read'] ? 'read' : ''; ?>">
                <div style="display:flex;justify-content:space-between;align-items:start;">
                    <div style="flex:1;">
                        <div style="margin-bottom:10px;">
                            <span class="severity-badge severity-<?php echo strtolower($alert['severity']); ?>">
                                <?php echo $alert['severity']; ?>
                            </span>
                            <span style="margin-left:10px;font-weight:600;color:#2c3e50;">
                                <?php echo $alert['alert_type']; ?>
                            </span>
                            <?php if (!$alert['is_read']): ?>
                                <span style="margin-left:10px;background:#3498db;color:white;padding:2px 8px;border-radius:10px;font-size:0.8em;">NEW</span>
                            <?php endif; ?>
                        </div>
                        
                        <div style="margin-bottom:10px;">
                            <strong><i class="fas fa-user"></i> Student:</strong> 
                            <?php echo htmlspecialchars($alert['student_name']); ?> 
                            (<?php echo htmlspecialchars($alert['student_id']); ?>)
                        </div>
                        
                        <div style="background:#f8f9fa;padding:12px;border-radius:4px;margin:10px 0;">
                            <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($alert['alert_message']); ?>
                        </div>
                        
                        <div style="font-size:0.9em;color:#7f8c8d;">
                            <i class="fas fa-clock"></i> <?php echo date('M d, Y h:i A', strtotime($alert['notified_at'])); ?>
                            <?php if ($alert['email']): ?>
                                | <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($alert['email']); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div style="margin-left:20px;">
                        <?php if (!$alert['is_read']): ?>
                            <form method="POST" action="">
                                <input type="hidden" name="alert_id" value="<?php echo $alert['id']; ?>">
                                <button type="submit" name="mark_read" class="btn btn-success btn-sm">
                                    <i class="fas fa-check"></i> Mark Read
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div style="text-align:center;padding:40px;color:#7f8c8d;">
            <i class="fas fa-check-circle" style="font-size:3em;margin-bottom:15px;color:#27ae60;"></i>
            <p>No attendance alerts. All students have good attendance records!</p>
        </div>
    <?php endif; ?>
</div>

<script>
// Auto-refresh list every 5 minutes
setTimeout(function() {
    location.reload();
}, 300000);

// Parent SMS: poll for consecutive absences (yesterday + today) while this page is open
(function() {
    var logEl = document.getElementById('parent-sms-poll-log');
    function tick() {
        var tickUrl = new URL('../ajax/parent_absence_sms_tick.php', window.location.href).href;
        fetch(tickUrl, { credentials: 'same-origin' })
            .then(function(r) { return r.text(); })
            .then(function(text) {
                var data;
                try {
                    data = JSON.parse(text);
                } catch (ignore) {
                    if (logEl) {
                        logEl.style.display = 'block';
                        logEl.textContent = new Date().toLocaleTimeString() + ' — Invalid JSON: ' + text.substring(0, 200);
                    }
                    return;
                }
                if (!logEl) return;
                if (data.ok === false && data.error) {
                    logEl.style.display = 'block';
                    logEl.textContent = new Date().toLocaleTimeString() + ' — ' + JSON.stringify(data);
                } else if (data.sent > 0 || (data.errors && data.errors.length)) {
                    logEl.style.display = 'block';
                    logEl.textContent = new Date().toLocaleTimeString() + ' — ' + JSON.stringify(data);
                }
            })
            .catch(function() { /* ignore */ });
    }
    tick();
    setInterval(tick, 120000);
})();
</script>
