<?php
// One-Click Report Generation System
// Database connection is already included from dashboard.php

// Handle report generation
if (isset($_GET['generate'])) {
    $report_type = $_GET['report_type'] ?? 'attendance';
    $format = $_GET['format'] ?? 'excel';
    $date_from = $_GET['date_from'] ?? date('Y-m-01');
    $date_to = $_GET['date_to'] ?? date('Y-m-d');
    $report_id = $_GET['report_id'] ?? null;
    
    // If viewing an existing report
    if ($report_id) {
        $sql = "SELECT * FROM reports WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $report_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $report = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        if ($report) {
            // For simplicity, we'll just display the report data
            // In a real system, you might regenerate or retrieve the actual report
            header('Content-Type: text/html; charset=UTF-8');
            echo '<!DOCTYPE html><html><head><meta charset="UTF-8">';
            echo '<title>' . htmlspecialchars($report['title']) . '</title>';
            echo '<style>body { font-family: Arial, sans-serif; margin: 20px; }</style>';
            echo '</head><body>';
            echo '<h1>' . htmlspecialchars($report['title']) . '</h1>';
            echo '<p><strong>Generated on:</strong> ' . htmlspecialchars($report['created_at']) . '</p>';
            echo '<pre>' . htmlspecialchars($report['report_data']) . '</pre>';
            echo '<button onclick="window.print()">Print Report</button>';
            echo '</body></html>';
            exit;
        }
    }
    
    // Generate new report
    if ($format == 'excel') {
        generateExcelReport($conn, $report_type, $date_from, $date_to);
    } elseif ($format == 'pdf') {
        generatePDFReport($conn, $report_type, $date_from, $date_to);
    } elseif ($format == 'csv') {
        generateCSVReport($conn, $report_type, $date_from, $date_to);
    }
    
    // Save report to database
    saveReportToDatabase($conn, $report_type, $format, $date_from, $date_to);
    exit;
}

function saveReportToDatabase($conn, $report_type, $format, $date_from, $date_to) {
    // Generate a title for the report
    $titles = [
        'attendance' => 'Student Attendance Report',
        'tutor_matching' => 'Tutor-Student Matching Report',
        'staff_attendance' => 'Staff Attendance Report',
        'student_performance' => 'Student Performance Report'
    ];
    
    $title = ($titles[$report_type] ?? 'Report') . ' - ' . date('M j, Y');
    $description = "Report generated for period: {$date_from} to {$date_to}";
    $report_data = "Report data for {$report_type} from {$date_from} to {$date_to}";
    
    $sql = "INSERT INTO reports (report_type, title, description, report_data, format, generated_by) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sssssi", $report_type, $title, $description, $report_data, $format, $_SESSION['id']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function generateExcelReport($conn, $report_type, $date_from, $date_to) {
    // Clear any output buffers to prevent headers already sent error
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $report_type . '_report_' . date('Y-m-d') . '.xls"');
    
    echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
    echo '<head><meta charset="UTF-8"><style>table {border-collapse: collapse;} th, td {border: 1px solid #000; padding: 8px;}</style></head>';
    echo '<body>';
    
    if ($report_type == 'attendance') {
        echo '<h2>Student Attendance Report</h2>';
        echo '<p>Period: ' . htmlspecialchars($date_from) . ' to ' . htmlspecialchars($date_to) . '</p>';
        echo '<table>';
        echo '<tr style="background-color:#4CAF50;color:white;">
                <th>Student ID</th>
                <th>Student Name</th>
                <th>Date</th>
                <th>Check-In</th>
                <th>Check-Out</th>
                <th>Status</th>
                <th>Biometric Verified</th>
              </tr>';
        
        $sql = "SELECT s.student_id, CONCAT(s.first_name, ' ', s.last_name) as name, 
                       sa.attendance_date, sa.check_in_time, sa.check_out_time, sa.status, sa.is_biometric_verified
                FROM student_attendance sa
                JOIN students s ON sa.student_id = s.id
                WHERE sa.attendance_date BETWEEN ? AND ?
                ORDER BY sa.attendance_date DESC, s.student_id";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $date_from, $date_to);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while ($row = mysqli_fetch_assoc($result)) {
            $status_color = $row['status'] == 'Present' ? '#d4edda' : ($row['status'] == 'Late' ? '#fff3cd' : '#f8d7da');
            echo '<tr style="background-color:' . $status_color . ';">';
            echo '<td>' . htmlspecialchars($row['student_id']) . '</td>';
            echo '<td>' . htmlspecialchars($row['name']) . '</td>';
            echo '<td>' . htmlspecialchars($row['attendance_date']) . '</td>';
            echo '<td>' . htmlspecialchars($row['check_in_time'] ?? 'N/A') . '</td>';
            echo '<td>' . htmlspecialchars($row['check_out_time'] ?? 'N/A') . '</td>';
            echo '<td>' . htmlspecialchars($row['status']) . '</td>';
            echo '<td>' . ($row['is_biometric_verified'] ? 'Yes' : 'No') . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
        mysqli_stmt_close($stmt);
        
    } elseif ($report_type == 'tutor_matching') {
        echo '<h2>Tutor-Student Matching Report</h2>';
        echo '<table>';
        echo '<tr style="background-color:#2196F3;color:white;">
                <th>Student</th>
                <th>Tutor</th>
                <th>Subject</th>
                <th>Status</th>
                <th>Start Date</th>
                <th>End Date</th>
              </tr>';
        
        $sql = "SELECT CONCAT(s.first_name, ' ', s.last_name) as student_name,
                       CONCAT(t.first_name, ' ', t.last_name) as tutor_name,
                       tsm.subject, tsm.status, tsm.start_date, tsm.end_date
                FROM tutor_student_matching tsm
                JOIN students s ON tsm.student_id = s.id
                JOIN tutors t ON tsm.tutor_id = t.id
                ORDER BY tsm.created_at DESC";
        
        $result = mysqli_query($conn, $sql);
        
        while ($row = mysqli_fetch_assoc($result)) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['student_name']) . '</td>';
            echo '<td>' . htmlspecialchars($row['tutor_name']) . '</td>';
            echo '<td>' . htmlspecialchars($row['subject']) . '</td>';
            echo '<td>' . htmlspecialchars($row['status']) . '</td>';
            echo '<td>' . htmlspecialchars($row['start_date']) . '</td>';
            echo '<td>' . htmlspecialchars($row['end_date'] ?? 'Ongoing') . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
        
    } elseif ($report_type == 'staff_attendance') {
        echo '<h2>Staff Attendance Report</h2>';
        echo '<p>Period: ' . htmlspecialchars($date_from) . ' to ' . htmlspecialchars($date_to) . '</p>';
        echo '<table>';
        echo '<tr style="background-color:#FF9800;color:white;">
                <th>Staff ID</th>
                <th>Staff Name</th>
                <th>Date</th>
                <th>Check-In</th>
                <th>Check-Out</th>
                <th>Status</th>
              </tr>';
        
        $sql = "SELECT u.id as staff_id, u.name as staff_name,
                       sa.attendance_date, sa.check_in_time, sa.check_out_time, sa.status
                FROM staff_attendance sa
                JOIN users u ON sa.staff_id = u.id
                WHERE u.role = 'user' AND sa.attendance_date BETWEEN ? AND ?
                ORDER BY sa.attendance_date DESC, u.name";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $date_from, $date_to);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while ($row = mysqli_fetch_assoc($result)) {
            $status_color = $row['status'] == 'Present' ? '#d4edda' : ($row['status'] == 'Late' ? '#fff3cd' : '#f8d7da');
            echo '<tr style="background-color:' . $status_color . ';">';
            echo '<td>' . htmlspecialchars($row['staff_id']) . '</td>';
            echo '<td>' . htmlspecialchars($row['staff_name']) . '</td>';
            echo '<td>' . htmlspecialchars($row['attendance_date']) . '</td>';
            echo '<td>' . htmlspecialchars($row['check_in_time'] ?? 'N/A') . '</td>';
            echo '<td>' . htmlspecialchars($row['check_out_time'] ?? 'N/A') . '</td>';
            echo '<td>' . htmlspecialchars($row['status']) . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
        mysqli_stmt_close($stmt);
    }
    
    echo '<br><p><i>Generated on: ' . date('Y-m-d H:i:s') . '</i></p>';
    echo '</body></html>';
}

function generatePDFReport($conn, $report_type, $date_from, $date_to) {
    // Clear any output buffers to prevent headers already sent error
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // For PDF generation, you would typically use a library like TCPDF or FPDF
    // This is a simplified HTML version that can be printed as PDF
    header('Content-Type: text/html; charset=UTF-8');
    
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8">';
    echo '<style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h2 { color: #2c3e50; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #34495e; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .header { text-align: center; margin-bottom: 30px; }
        .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #7f8c8d; }
        @media print {
            .no-print { display: none; }
        }
    </style></head><body>';
    
    echo '<div class="header">';
    echo '<h1>Attendance Management System</h1>';
    echo '<h2>' . ucwords(str_replace('_', ' ', $report_type)) . ' Report</h2>';
    echo '<p>Period: ' . htmlspecialchars($date_from) . ' to ' . htmlspecialchars($date_to) . '</p>';
    echo '</div>';
    
    echo '<button class="no-print" onclick="window.print()" style="padding:10px 20px;background:#3498db;color:white;border:none;border-radius:4px;cursor:pointer;">Print/Save as PDF</button>';
    
    if ($report_type == 'attendance') {
        echo '<table>';
        echo '<tr>
                <th>Student ID</th>
                <th>Student Name</th>
                <th>Date</th>
                <th>Check-In</th>
                <th>Check-Out</th>
                <th>Status</th>
              </tr>';
        
        $sql = "SELECT s.student_id, CONCAT(s.first_name, ' ', s.last_name) as name, 
                       sa.attendance_date, sa.check_in_time, sa.check_out_time, sa.status
                FROM student_attendance sa
                JOIN students s ON sa.student_id = s.id
                WHERE sa.attendance_date BETWEEN ? AND ?
                ORDER BY sa.attendance_date DESC, s.student_id";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $date_from, $date_to);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while ($row = mysqli_fetch_assoc($result)) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['student_id']) . '</td>';
            echo '<td>' . htmlspecialchars($row['name']) . '</td>';
            echo '<td>' . htmlspecialchars($row['attendance_date']) . '</td>';
            echo '<td>' . htmlspecialchars($row['check_in_time'] ?? 'N/A') . '</td>';
            echo '<td>' . htmlspecialchars($row['check_out_time'] ?? 'N/A') . '</td>';
            echo '<td>' . htmlspecialchars($row['status']) . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
        mysqli_stmt_close($stmt);
        
    } elseif ($report_type == 'tutor_matching') {
        echo '<table>';
        echo '<tr>
                <th>Student</th>
                <th>Tutor</th>
                <th>Subject</th>
                <th>Status</th>
                <th>Start Date</th>
                <th>End Date</th>
              </tr>';
        
        $sql = "SELECT CONCAT(s.first_name, ' ', s.last_name) as student_name,
                       CONCAT(t.first_name, ' ', t.last_name) as tutor_name,
                       tsm.subject, tsm.status, tsm.start_date, tsm.end_date
                FROM tutor_student_matching tsm
                JOIN students s ON tsm.student_id = s.id
                JOIN tutors t ON tsm.tutor_id = t.id
                ORDER BY tsm.created_at DESC";
        
        $result = mysqli_query($conn, $sql);
        
        while ($row = mysqli_fetch_assoc($result)) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['student_name']) . '</td>';
            echo '<td>' . htmlspecialchars($row['tutor_name']) . '</td>';
            echo '<td>' . htmlspecialchars($row['subject']) . '</td>';
            echo '<td>' . htmlspecialchars($row['status']) . '</td>';
            echo '<td>' . htmlspecialchars($row['start_date']) . '</td>';
            echo '<td>' . htmlspecialchars($row['end_date'] ?? 'Ongoing') . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
        
    } elseif ($report_type == 'staff_attendance') {
        echo '<table>';
        echo '<tr>
                <th>Staff ID</th>
                <th>Staff Name</th>
                <th>Date</th>
                <th>Check-In</th>
                <th>Check-Out</th>
                <th>Status</th>
              </tr>';
        
        $sql = "SELECT u.id as staff_id, u.name as staff_name,
                       sa.attendance_date, sa.check_in_time, sa.check_out_time, sa.status
                FROM staff_attendance sa
                JOIN users u ON sa.staff_id = u.id
                WHERE u.role = 'user' AND sa.attendance_date BETWEEN ? AND ?
                ORDER BY sa.attendance_date DESC, u.name";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $date_from, $date_to);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while ($row = mysqli_fetch_assoc($result)) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['staff_id']) . '</td>';
            echo '<td>' . htmlspecialchars($row['staff_name']) . '</td>';
            echo '<td>' . htmlspecialchars($row['attendance_date']) . '</td>';
            echo '<td>' . htmlspecialchars($row['check_in_time'] ?? 'N/A') . '</td>';
            echo '<td>' . htmlspecialchars($row['check_out_time'] ?? 'N/A') . '</td>';
            echo '<td>' . htmlspecialchars($row['status']) . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
        mysqli_stmt_close($stmt);
    }
    
    echo '<div class="footer">';
    echo '<p>Generated on: ' . date('Y-m-d H:i:s') . '</p>';
    echo '<p>This is an official document from the Attendance Management System</p>';
    echo '</div>';
    
    echo '</body></html>';
}

function generateCSVReport($conn, $report_type, $date_from, $date_to) {
    // Clear any output buffers to prevent headers already sent error
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $report_type . '_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    if ($report_type == 'attendance') {
        fputcsv($output, ['Student ID', 'Student Name', 'Date', 'Check-In', 'Check-Out', 'Status', 'Biometric Verified']);
        
        $sql = "SELECT s.student_id, CONCAT(s.first_name, ' ', s.last_name) as name, 
                       sa.attendance_date, sa.check_in_time, sa.check_out_time, sa.status, sa.is_biometric_verified
                FROM student_attendance sa
                JOIN students s ON sa.student_id = s.id
                WHERE sa.attendance_date BETWEEN ? AND ?
                ORDER BY sa.attendance_date DESC, s.student_id";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $date_from, $date_to);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while ($row = mysqli_fetch_assoc($result)) {
            fputcsv($output, [
                $row['student_id'],
                $row['name'],
                $row['attendance_date'],
                $row['check_in_time'] ?? 'N/A',
                $row['check_out_time'] ?? 'N/A',
                $row['status'],
                $row['is_biometric_verified'] ? 'Yes' : 'No'
            ]);
        }
        
        mysqli_stmt_close($stmt);
        
    } elseif ($report_type == 'tutor_matching') {
        fputcsv($output, ['Student', 'Tutor', 'Subject', 'Status', 'Start Date', 'End Date']);
        
        $sql = "SELECT CONCAT(s.first_name, ' ', s.last_name) as student_name,
                       CONCAT(t.first_name, ' ', t.last_name) as tutor_name,
                       tsm.subject, tsm.status, tsm.start_date, tsm.end_date
                FROM tutor_student_matching tsm
                JOIN students s ON tsm.student_id = s.id
                JOIN tutors t ON tsm.tutor_id = t.id
                ORDER BY tsm.created_at DESC";
        
        $result = mysqli_query($conn, $sql);
        
        while ($row = mysqli_fetch_assoc($result)) {
            fputcsv($output, [
                $row['student_name'],
                $row['tutor_name'],
                $row['subject'],
                $row['status'],
                $row['start_date'],
                $row['end_date'] ?? 'Ongoing'
            ]);
        }
        
    } elseif ($report_type == 'staff_attendance') {
        fputcsv($output, ['Staff ID', 'Staff Name', 'Date', 'Check-In', 'Check-Out', 'Status']);
        
        $sql = "SELECT u.id as staff_id, u.name as staff_name,
                       sa.attendance_date, sa.check_in_time, sa.check_out_time, sa.status
                FROM staff_attendance sa
                JOIN users u ON sa.staff_id = u.id
                WHERE u.role = 'user' AND sa.attendance_date BETWEEN ? AND ?
                ORDER BY sa.attendance_date DESC, u.name";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $date_from, $date_to);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while ($row = mysqli_fetch_assoc($result)) {
            fputcsv($output, [
                $row['staff_id'],
                $row['staff_name'],
                $row['attendance_date'],
                $row['check_in_time'] ?? 'N/A',
                $row['check_out_time'] ?? 'N/A',
                $row['status']
            ]);
        }
        
        mysqli_stmt_close($stmt);
    }
    
    fclose($output);
}
?>

<style>
    .report-card {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        padding: 30px;
    }
    .report-options {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-top: 30px;
    }
    .report-option {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        border-radius: 8px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
        text-decoration: none;
        display: block;
    }
    .report-option:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 16px rgba(102, 126, 234, 0.4);
        color: white;
        text-decoration: none;
    }
    .report-option i {
        font-size: 3em;
        margin-bottom: 15px;
    }
    .report-option h3 {
        margin: 10px 0;
        font-size: 1.3em;
    }
    .form-group {
        margin-bottom: 20px;
    }
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #2c3e50;
    }
    .form-control {
        width: 100%;
        padding: 10px 15px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
    }
    .btn {
        padding: 12px 30px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        margin-right: 10px;
        text-decoration: none;
        display: inline-block;
    }
    .btn-success {
        background: #27ae60;
        color: #fff;
    }
    .btn-danger {
        background: #e74c3c;
        color: #fff;
    }
</style>

<div class="report-card">
    <h2><i class="fas fa-file-export"></i> One-Click Report Generation</h2>
    <p>Generate comprehensive reports in Excel or PDF format with a single click</p>

    <form method="GET" action="">
        <input type="hidden" name="page" value="generate_reports">
        <input type="hidden" name="generate" value="1">
        
        <div class="form-group">
            <label for="report_type">Report Type</label>
            <select class="form-control" id="report_type" name="report_type" required>
                <option value="attendance">Student Attendance Report</option>
                <option value="tutor_matching">Tutor-Student Matching Report</option>
                <option value="staff_attendance">Staff Attendance Report</option>
            </select>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
            <div class="form-group">
                <label for="date_from">From Date</label>
                <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo date('Y-m-01'); ?>" required>
            </div>

            <div class="form-group">
                <label for="date_to">To Date</label>
                <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
        </div>

        <div style="margin-top:30px;">
            <button type="submit" name="format" value="excel" class="btn btn-success">
                <i class="fas fa-file-excel"></i> Generate Excel Report
            </button>
            <button type="submit" name="format" value="pdf" class="btn btn-danger">
                <i class="fas fa-file-pdf"></i> Generate PDF Report
            </button>
        </div>
    </form>

    <div class="report-options">
        <div class="report-option" onclick="quickReport('attendance', 'excel')">
            <i class="fas fa-calendar-check"></i>
            <h3>Attendance Report</h3>
            <p>Quick Excel Export</p>
        </div>
        
        <div class="report-option" onclick="quickReport('tutor_matching', 'excel')">
            <i class="fas fa-chalkboard-teacher"></i>
            <h3>Tutor Matching</h3>
            <p>Quick Excel Export</p>
        </div>
        
        <div class="report-option" onclick="quickReport('attendance', 'pdf')">
            <i class="fas fa-file-pdf"></i>
            <h3>PDF Report</h3>
            <p>Print-Ready Format</p>
        </div>
    </div>
</div>

<script>
function quickReport(type, format) {
    const dateFrom = '<?php echo date('Y-m-01'); ?>';
    const dateTo = '<?php echo date('Y-m-d'); ?>';
    window.location.href = `?page=generate_reports&generate=1&report_type=${type}&format=${format}&date_from=${dateFrom}&date_to=${dateTo}`;
}
</script>
