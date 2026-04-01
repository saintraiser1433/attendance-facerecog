<?php
session_start();
include "../db_conn.php";

// Check if admin is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit;
}

// Get parameters
$format = isset($_GET['format']) ? $_GET['format'] : 'excel';
$tutor_id = isset($_GET['tutor_id']) ? intval($_GET['tutor_id']) : 0;
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'All';

// Build query
$sql = "SELECT 
            ta.attendance_date,
            t.tutor_id as tutor_code,
            CONCAT(t.first_name, ' ', t.last_name) as tutor_name,
            t.specialization,
            t.email,
            t.phone,
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

if ($tutor_id > 0) {
    $sql .= " AND ta.tutor_id = ?";
    $params[] = $tutor_id;
    $types .= "i";
}

if ($status_filter != 'All') {
    $sql .= " AND ta.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$sql .= " ORDER BY ta.attendance_date DESC, ta.check_in_time DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($format == 'excel') {
    // Excel Export
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=tutor_attendance_" . date('Y-m-d') . ".xls");
    header("Pragma: no-cache");
    header("Expires: 0");
    
    echo "<table border='1'>";
    echo "<thead style='background-color:#34495e;color:white;font-weight:bold;'>";
    echo "<tr>";
    echo "<th>Date</th>";
    echo "<th>Tutor ID</th>";
    echo "<th>Tutor Name</th>";
    echo "<th>Specialization</th>";
    echo "<th>Email</th>";
    echo "<th>Phone</th>";
    echo "<th>Check In</th>";
    echo "<th>Check Out</th>";
    echo "<th>Hours Worked</th>";
    echo "<th>Status</th>";
    echo "<th>Biometric Verified</th>";
    echo "<th>Match Score</th>";
    echo "<th>Notes</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        // Color code based on status
        $bg_color = '';
        switch($row['status']) {
            case 'Present':
                $bg_color = '#d4edda';
                break;
            case 'Absent':
                $bg_color = '#f8d7da';
                break;
            case 'Late':
                $bg_color = '#fff3cd';
                break;
            case 'Excused':
                $bg_color = '#d1ecf1';
                break;
        }
        
        echo "<tr style='background-color:{$bg_color};'>";
        echo "<td>" . date('M d, Y', strtotime($row['attendance_date'])) . "</td>";
        echo "<td>" . htmlspecialchars($row['tutor_code']) . "</td>";
        echo "<td>" . htmlspecialchars($row['tutor_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['specialization']) . "</td>";
        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
        echo "<td>" . htmlspecialchars($row['phone']) . "</td>";
        echo "<td>" . ($row['check_in_time'] ? date('g:i A', strtotime($row['check_in_time'])) : '-') . "</td>";
        echo "<td>" . ($row['check_out_time'] ? date('g:i A', strtotime($row['check_out_time'])) : '-') . "</td>";
        echo "<td>" . ($row['hours_worked'] ?? '-') . "</td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "<td>" . ($row['is_biometric_verified'] ? 'Yes' : 'No') . "</td>";
        echo "<td>" . ($row['fingerprint_match_score'] > 0 ? number_format($row['fingerprint_match_score'], 1) . '%' : '-') . "</td>";
        echo "<td>" . htmlspecialchars($row['notes'] ?? '-') . "</td>";
        echo "</tr>";
    }
    
    echo "</tbody>";
    echo "</table>";
    
} else if ($format == 'pdf') {
    // PDF Export (Simple HTML to PDF)
    header("Content-Type: application/pdf");
    header("Content-Disposition: attachment; filename=tutor_attendance_" . date('Y-m-d') . ".pdf");
    
    // For a simple PDF, we'll use HTML that browsers can print to PDF
    // In production, use a library like TCPDF or FPDF
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Tutor Attendance Report</title>
        <style>
            body { font-family: Arial, sans-serif; font-size: 12px; }
            h1 { text-align: center; color: #2c3e50; }
            .info { text-align: center; margin-bottom: 20px; color: #7f8c8d; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #34495e; color: white; }
            .present { background-color: #d4edda; }
            .absent { background-color: #f8d7da; }
            .late { background-color: #fff3cd; }
            .excused { background-color: #d1ecf1; }
        </style>
    </head>
    <body>
        <h1>Tutor/Teacher Attendance Report</h1>
        <div class="info">
            <p>Period: <?php echo date('M d, Y', strtotime($date_from)); ?> to <?php echo date('M d, Y', strtotime($date_to)); ?></p>
            <p>Generated: <?php echo date('M d, Y g:i A'); ?></p>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Tutor ID</th>
                    <th>Name</th>
                    <th>Specialization</th>
                    <th>Check In</th>
                    <th>Check Out</th>
                    <th>Hours</th>
                    <th>Status</th>
                    <th>Verified</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                mysqli_data_seek($result, 0);
                while ($row = mysqli_fetch_assoc($result)): 
                ?>
                    <tr class="<?php echo strtolower($row['status']); ?>">
                        <td><?php echo date('M d, Y', strtotime($row['attendance_date'])); ?></td>
                        <td><?php echo htmlspecialchars($row['tutor_code']); ?></td>
                        <td><?php echo htmlspecialchars($row['tutor_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['specialization']); ?></td>
                        <td><?php echo $row['check_in_time'] ? date('g:i A', strtotime($row['check_in_time'])) : '-'; ?></td>
                        <td><?php echo $row['check_out_time'] ? date('g:i A', strtotime($row['check_out_time'])) : '-'; ?></td>
                        <td><?php echo $row['hours_worked'] ?? '-'; ?></td>
                        <td><?php echo $row['status']; ?></td>
                        <td><?php echo $row['is_biometric_verified'] ? 'Yes' : 'No'; ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
        <script>
            window.onload = function() { window.print(); }
        </script>
    </body>
    </html>
    <?php
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
