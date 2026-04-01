<?php
// Get all students with their year level information and tutor information
$sql = "SELECT s.*, y.year_level_name, t.first_name as tutor_first_name, t.last_name as tutor_last_name, tsm.subject
        FROM students s 
        LEFT JOIN year_levels y ON s.year_level_id = y.id 
        LEFT JOIN tutor_student_matching tsm ON s.id = tsm.student_id AND tsm.status = 'Active'
        LEFT JOIN tutors t ON tsm.tutor_id = t.id
        ORDER BY s.last_name, s.first_name";
$result = mysqli_query($conn, $sql);
?>

<div style="background:#fff;padding:30px;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);">
    <h2><i class="fas fa-users"></i> Student List</h2>
    <p>View all registered students and their assigned tutors in the system.</p>
    
    <?php if ($result && mysqli_num_rows($result) > 0): ?>
        <div style="overflow-x:auto;margin-top:20px;">
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="background:#f8f9fa;">
                        <th style="padding:12px;text-align:left;border-bottom:2px solid #dee2e6;">Student ID</th>
                        <th style="padding:12px;text-align:left;border-bottom:2px solid #dee2e6;">Name</th>
                        <th style="padding:12px;text-align:left;border-bottom:2px solid #dee2e6;">Email</th>
                        <th style="padding:12px;text-align:left;border-bottom:2px solid #dee2e6;">Year Level</th>
                        <th style="padding:12px;text-align:left;border-bottom:2px solid #dee2e6;">Section</th>
                        <th style="padding:12px;text-align:left;border-bottom:2px solid #dee2e6;">Assigned Tutor</th>
                        <th style="padding:12px;text-align:left;border-bottom:2px solid #dee2e6;">Subject</th>
                        <th style="padding:12px;text-align:left;border-bottom:2px solid #dee2e6;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($student = mysqli_fetch_assoc($result)): ?>
                        <tr style="border-bottom:1px solid #dee2e6;">
                            <td style="padding:12px;"><?php echo htmlspecialchars($student['student_id']); ?></td>
                            <td style="padding:12px;"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                            <td style="padding:12px;"><?php echo htmlspecialchars($student['email']); ?></td>
                            <td style="padding:12px;"><?php echo htmlspecialchars($student['year_level_name'] ?? 'N/A'); ?></td>
                            <td style="padding:12px;"><?php echo htmlspecialchars($student['section'] ?? 'N/A'); ?></td>
                            <td style="padding:12px;">
                                <?php 
                                if (!empty($student['tutor_first_name']) && !empty($student['tutor_last_name'])) {
                                    echo htmlspecialchars($student['tutor_first_name'] . ' ' . $student['tutor_last_name']);
                                } else {
                                    echo '<span style="color:#6c757d;">No tutor assigned</span>';
                                }
                                ?>
                            </td>
                            <td style="padding:12px;"><?php echo htmlspecialchars($student['subject'] ?? 'N/A'); ?></td>
                            <td style="padding:12px;">
                                <span style="padding:4px 8px;border-radius:4px;font-size:0.85em;
                                    <?php 
                                    switch($student['status']) {
                                        case 'Active': echo 'background:#d4edda;color:#155724;'; break;
                                        case 'Inactive': echo 'background:#f8d7da;color:#721c24;'; break;
                                        case 'Graduated': echo 'background:#d1ecf1;color:#0c5460;'; break;
                                        default: echo 'background:#f8f9fa;color:#212529;';
                                    }
                                    ?>">
                                    <?php echo htmlspecialchars($student['status']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div style="background:#f8d7da;color:#721c24;padding:15px;border-radius:4px;margin-top:20px;">
            <strong>No students found.</strong> There are no students registered in the system yet.
        </div>
    <?php endif; ?>
</div>