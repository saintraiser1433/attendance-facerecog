<?php
// Fetch tutor-student matching data
$sql = "SELECT tsm.*, 
        CONCAT(t.first_name, ' ', t.last_name) as tutor_name,
        CONCAT(s.first_name, ' ', s.last_name) as student_name,
        t.tutor_id, s.student_id
        FROM tutor_student_matching tsm
        LEFT JOIN tutors t ON tsm.tutor_id = t.id
        LEFT JOIN students s ON tsm.student_id = s.id
        ORDER BY tsm.created_at DESC";
$result = mysqli_query($conn, $sql);
?>

<style>
    .card {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        padding: 20px;
    }
    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f0f0f0;
    }
    .card-title {
        font-size: 1.5em;
        font-weight: bold;
        color: #2c3e50;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        background: #fff;
    }
    th, td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }
    th {
        background: #34495e;
        color: #fff;
        font-weight: bold;
    }
    tr:hover {
        background: #f5f6fa;
    }
    .badge {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 0.85em;
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
    .badge-completed {
        background: #d1ecf1;
        color: #0c5460;
    }
</style>

<div class="card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-chalkboard-teacher"></i> Tutor-Student Matching</h2>
    </div>

    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>Tutor</th>
                    <th>Student</th>
                    <th>Subject</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $status_class = '';
                        switch($row['status']) {
                            case 'Active':
                                $status_class = 'badge-active';
                                break;
                            case 'Inactive':
                                $status_class = 'badge-inactive';
                                break;
                            case 'Completed':
                                $status_class = 'badge-completed';
                                break;
                        }
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($row['tutor_name'] ?? 'N/A') . '</td>';
                        echo '<td>' . htmlspecialchars($row['student_name'] ?? 'N/A') . '</td>';
                        echo '<td>' . htmlspecialchars($row['subject']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['start_date']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['end_date'] ?? 'Ongoing') . '</td>';
                        echo '<td><span class="badge ' . $status_class . '">' . htmlspecialchars($row['status']) . '</span></td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="6" style="text-align:center;">No tutor-student matching records found.</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
</div>
