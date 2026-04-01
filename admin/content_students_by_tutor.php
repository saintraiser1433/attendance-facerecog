<?php
// Fetch tutors and their assigned students
$tutors_sql = "SELECT t.id, t.tutor_id, CONCAT(t.first_name, ' ', t.last_name) AS tutor_name, t.specialization, t.status
               FROM tutors t
               ORDER BY t.first_name, t.last_name";
$tutors_result = mysqli_query($conn, $tutors_sql);

// Map tutor IDs to their students
$students_by_tutor = [];
$student_counts = [];

if ($tutors_result && mysqli_num_rows($tutors_result) > 0) {
    while ($tutor = mysqli_fetch_assoc($tutors_result)) {
        $tutor_id = (int)$tutor['id'];
        $students_by_tutor[$tutor_id] = [
            'info' => $tutor,
            'students' => []
        ];
        $student_counts[$tutor_id] = 0;
    }

    if (!empty($students_by_tutor)) {
        $student_sql = "SELECT tsm.tutor_id,
                                s.student_id,
                                CONCAT(s.first_name, ' ', s.last_name) AS student_name,
                                s.email,
                                s.phone,
                                s.status AS student_status,
                                tsm.subject,
                                tsm.start_date,
                                tsm.status AS match_status
                         FROM tutor_student_matching tsm
                         LEFT JOIN students s ON tsm.student_id = s.id
                         WHERE tsm.tutor_id IN (" . implode(',', array_keys($students_by_tutor)) . ")
                         ORDER BY s.first_name, s.last_name";
        $student_result = mysqli_query($conn, $student_sql);

        if ($student_result && mysqli_num_rows($student_result) > 0) {
            while ($row = mysqli_fetch_assoc($student_result)) {
                $tutor_id = (int)$row['tutor_id'];
                if (isset($students_by_tutor[$tutor_id])) {
                    $students_by_tutor[$tutor_id]['students'][] = $row;
                    $student_counts[$tutor_id]++;
                }
            }
        }
    }
}
?>

<style>
    .students-by-tutor {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 20px;
    }
    .tutor-card {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        padding: 20px;
        display: flex;
        flex-direction: column;
    }
    .tutor-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 15px;
    }
    .tutor-name {
        font-size: 1.2em;
        font-weight: bold;
        color: #2c3e50;
        margin: 0;
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
    .badge-leave {
        background: #fff3cd;
        color: #856404;
    }
    .students-list {
        margin: 0;
        padding: 0;
        list-style: none;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .student-item {
        background: #f8f9fa;
        border-radius: 6px;
        padding: 12px;
    }
    .student-item strong {
        color: #2c3e50;
    }
    .student-meta {
        font-size: 0.9em;
        color: #7f8c8d;
        margin-top: 6px;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .empty-state {
        text-align: center;
        padding: 40px;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .empty-state i {
        font-size: 3em;
        color: #bdc3c7;
        margin-bottom: 15px;
    }
</style>

<div class="tutor-card" style="margin-bottom:20px;">
    <h2 class="tutor-name"><i class="fas fa-users"></i> Students by Tutor</h2>
    <p style="color:#7f8c8d; margin-top:10px;">Overview of students grouped under each tutor assignment.</p>
</div>

<?php if (empty($students_by_tutor)) : ?>
    <div class="empty-state">
        <i class="fas fa-user-graduate"></i>
        <h3>No tutors found</h3>
        <p>Start by adding tutors and assigning students to view them here.</p>
    </div>
<?php else : ?>
    <div class="students-by-tutor">
        <?php foreach ($students_by_tutor as $tutor_data) :
            $info = $tutor_data['info'];
            $students = $tutor_data['students'];
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
                <div class="tutor-header">
                    <div>
                        <p class="tutor-name"><?php echo htmlspecialchars($info['tutor_name']); ?></p>
                        <p style="margin:4px 0 0;color:#7f8c8d;">
                            ID: <?php echo htmlspecialchars($info['tutor_id']); ?>
                            <?php if (!empty($info['specialization'])) : ?>
                                · Specialization: <?php echo htmlspecialchars($info['specialization']); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <span class="badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($info['status']); ?></span>
                </div>

                <?php if (!empty($students)) : ?>
                    <p style="margin:0 0 10px;font-weight:bold;color:#2c3e50;">Assigned Students (<?php echo count($students); ?>)</p>
                    <ul class="students-list">
                        <?php foreach ($students as $student) : ?>
                            <li class="student-item">
                                <strong><?php echo htmlspecialchars($student['student_name'] ?? 'Unknown'); ?></strong>
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
                    <p style="color:#7f8c8d;margin:0;">No students assigned yet.</p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
