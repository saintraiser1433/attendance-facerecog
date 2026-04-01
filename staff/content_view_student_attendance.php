<?php
// Get filter parameters
$filter_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$filter_year_level = isset($_GET['year_level']) ? $_GET['year_level'] : '';
$filter_section = isset($_GET['section']) ? $_GET['section'] : '';

// Get year levels for filter
$year_levels_sql = "SELECT * FROM year_levels WHERE status = 'Active' ORDER BY order_number";
$year_levels_result = mysqli_query($conn, $year_levels_sql);

// Build query for student attendance
$sql = "SELECT s.student_id, s.first_name, s.last_name, s.section, y.year_level_name, 
               sa.attendance_date, sa.status, sa.check_in_time, sa.check_out_time
        FROM students s
        LEFT JOIN year_levels y ON s.year_level_id = y.id
        LEFT JOIN student_attendance sa ON s.id = sa.student_id AND sa.attendance_date = ?
        WHERE s.status = 'Active'";

$params = [$filter_date];
$types = "s";

// Add filters
if (!empty($filter_year_level)) {
    $sql .= " AND s.year_level_id = ?";
    $params[] = $filter_year_level;
    $types .= "i";
}

if (!empty($filter_section)) {
    $sql .= " AND s.section = ?";
    $params[] = $filter_section;
    $types .= "s";
}

$sql .= " ORDER BY y.order_number, s.section, s.last_name, s.first_name";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Get unique sections for filter
$sections_sql = "SELECT DISTINCT section FROM students WHERE section IS NOT NULL AND section != '' ORDER BY section";
$sections_result = mysqli_query($conn, $sections_sql);
?>

<div style="background:#fff;padding:30px;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);">
    <h2><i class="fas fa-calendar-check"></i> Student Attendance</h2>
    <p>View student attendance records.</p>
    
    <!-- Filter Form -->
    <div style="background:#f8f9fa;padding:20px;border-radius:8px;margin:20px 0;">
        <h4><i class="fas fa-filter"></i> Filter Attendance</h4>
        <form method="GET" style="display:flex;flex-wrap:wrap;gap:15px;margin-top:15px;">
            <input type="hidden" name="page" value="view_attendance">
            
            <div>
                <label for="date" style="display:block;margin-bottom:5px;font-weight:600;">Date</label>
                <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($filter_date); ?>" 
                       style="padding:8px;border:1px solid #ddd;border-radius:4px;">
            </div>
            
            <div>
                <label for="year_level" style="display:block;margin-bottom:5px;font-weight:600;">Year Level</label>
                <select id="year_level" name="year_level" style="padding:8px;border:1px solid #ddd;border-radius:4px;">
                    <option value="">All Year Levels</option>
                    <?php 
                    mysqli_data_seek($year_levels_result, 0);
                    while ($yl = mysqli_fetch_assoc($year_levels_result)): ?>
                        <option value="<?php echo $yl['id']; ?>" <?php echo ($filter_year_level == $yl['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($yl['year_level_code'] . ' - ' . $yl['year_level_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div>
                <label for="section" style="display:block;margin-bottom:5px;font-weight:600;">Section</label>
                <select id="section" name="section" style="padding:8px;border:1px solid #ddd;border-radius:4px;">
                    <option value="">All Sections</option>
                    <?php 
                    mysqli_data_seek($sections_result, 0);
                    while ($sec = mysqli_fetch_assoc($sections_result)): ?>
                        <option value="<?php echo htmlspecialchars($sec['section']); ?>" <?php echo ($filter_section == $sec['section']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($sec['section']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div style="align-self:flex-end;">
                <button type="submit" style="padding:8px 15px;background:#3498db;color:white;border:none;border-radius:4px;cursor:pointer;">
                    <i class="fas fa-search"></i> Filter
                </button>
                <a href="?page=view_attendance" style="padding:8px 15px;background:#95a5a6;color:white;border-radius:4px;text-decoration:none;margin-left:5px;">
                    <i class="fas fa-times"></i> Clear
                </a>
            </div>
        </form>
    </div>
    
    <!-- Attendance Summary -->
    <?php if ($result && mysqli_num_rows($result) > 0): 
        // Calculate summary statistics
        $present_count = 0;
        $absent_count = 0;
        $late_count = 0;
        $excused_count = 0;
        $not_marked_count = 0;
        
        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
            if (empty($row['status'])) {
                $not_marked_count++;
            } else {
                switch ($row['status']) {
                    case 'Present': $present_count++; break;
                    case 'Absent': $absent_count++; break;
                    case 'Late': $late_count++; break;
                    case 'Excused': $excused_count++; break;
                }
            }
        }
        mysqli_data_seek($result, 0);
    ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:15px;margin:20px 0;">
            <div style="background:#d4edda;padding:15px;border-radius:8px;text-align:center;">
                <div style="font-size:1.5em;font-weight:bold;color:#155724;"><?php echo $present_count; ?></div>
                <div style="color:#155724;">Present</div>
            </div>
            <div style="background:#f8d7da;padding:15px;border-radius:8px;text-align:center;">
                <div style="font-size:1.5em;font-weight:bold;color:#721c24;"><?php echo $absent_count; ?></div>
                <div style="color:#721c24;">Absent</div>
            </div>
            <div style="background:#fff3cd;padding:15px;border-radius:8px;text-align:center;">
                <div style="font-size:1.5em;font-weight:bold;color:#856404;"><?php echo $late_count; ?></div>
                <div style="color:#856404;">Late</div>
            </div>
            <div style="background:#d1ecf1;padding:15px;border-radius:8px;text-align:center;">
                <div style="font-size:1.5em;font-weight:bold;color:#0c5460;"><?php echo $excused_count; ?></div>
                <div style="color:#0c5460;">Excused</div>
            </div>
            <div style="background:#e2e3e5;padding:15px;border-radius:8px;text-align:center;">
                <div style="font-size:1.5em;font-weight:bold;color:#41464b;"><?php echo $not_marked_count; ?></div>
                <div style="color:#41464b;">Not Marked</div>
            </div>
        </div>
        
        <!-- Attendance Table -->
        <div style="overflow-x:auto;margin-top:20px;">
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="background:#f8f9fa;">
                        <th style="padding:12px;text-align:left;border-bottom:2px solid #dee2e6;">Student ID</th>
                        <th style="padding:12px;text-align:left;border-bottom:2px solid #dee2e6;">Name</th>
                        <th style="padding:12px;text-align:left;border-bottom:2px solid #dee2e6;">Year Level</th>
                        <th style="padding:12px;text-align:left;border-bottom:2px solid #dee2e6;">Section</th>
                        <th style="padding:12px;text-align:left;border-bottom:2px solid #dee2e6;">Status</th>
                        <th style="padding:12px;text-align:left;border-bottom:2px solid #dee2e6;">Check-in Time</th>
                        <th style="padding:12px;text-align:left;border-bottom:2px solid #dee2e6;">Check-out Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $student): ?>
                        <tr style="border-bottom:1px solid #dee2e6;">
                            <td style="padding:12px;"><?php echo htmlspecialchars($student['student_id']); ?></td>
                            <td style="padding:12px;"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                            <td style="padding:12px;"><?php echo htmlspecialchars($student['year_level_name'] ?? 'N/A'); ?></td>
                            <td style="padding:12px;"><?php echo htmlspecialchars($student['section'] ?? 'N/A'); ?></td>
                            <td style="padding:12px;">
                                <?php if (!empty($student['status'])): ?>
                                    <span style="padding:4px 8px;border-radius:4px;font-size:0.85em;
                                        <?php 
                                        switch($student['status']) {
                                            case 'Present': echo 'background:#d4edda;color:#155724;'; break;
                                            case 'Absent': echo 'background:#f8d7da;color:#721c24;'; break;
                                            case 'Late': echo 'background:#fff3cd;color:#856404;'; break;
                                            case 'Excused': echo 'background:#d1ecf1;color:#0c5460;'; break;
                                            default: echo 'background:#f8f9fa;color:#212529;';
                                        }
                                        ?>">
                                        <?php echo htmlspecialchars($student['status']); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="padding:4px 8px;border-radius:4px;font-size:0.85em;background:#e2e3e5;color:#41464b;">
                                        Not Marked
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="padding:12px;"><?php echo htmlspecialchars($student['check_in_time'] ?? 'N/A'); ?></td>
                            <td style="padding:12px;"><?php echo htmlspecialchars($student['check_out_time'] ?? 'N/A'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div style="margin-top:20px;padding:15px;background:#f8f9fa;border-radius:8px;">
            <strong>Total Students:</strong> <?php echo count($data); ?> | 
            <strong>Date:</strong> <?php echo date('F j, Y', strtotime($filter_date)); ?>
        </div>
    <?php else: ?>
        <div style="background:#f8d7da;color:#721c24;padding:15px;border-radius:4px;margin-top:20px;">
            <strong>No attendance records found.</strong> There are no students matching your filter criteria or no attendance has been recorded for this date.
        </div>
    <?php endif; ?>
</div>