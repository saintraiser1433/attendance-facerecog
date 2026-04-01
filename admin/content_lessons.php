<?php
require_once __DIR__ . '/../includes/lesson_schema.php';
lessons_ensure_schema($conn);

$ok = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_lesson'])) {
    $lesson_name = trim((string) ($_POST['lesson_name'] ?? ''));
    if ($lesson_name === '') {
        $err = 'Lesson name is required.';
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO lessons (lesson_name, status) VALUES (?, 'Active')");
        mysqli_stmt_bind_param($stmt, "s", $lesson_name);
        if (mysqli_stmt_execute($stmt)) {
            $ok = 'Lesson added.';
        } else {
            $err = 'Lesson already exists or could not be added.';
        }
        mysqli_stmt_close($stmt);
    }
}

if (isset($_POST['toggle_lesson'])) {
    $lesson_id = (int) ($_POST['lesson_id'] ?? 0);
    $new_status = ($_POST['new_status'] ?? '') === 'Inactive' ? 'Inactive' : 'Active';
    if ($lesson_id > 0) {
        $stmt = mysqli_prepare($conn, "UPDATE lessons SET status = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $new_status, $lesson_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $ok = 'Lesson status updated.';
    }
}

$lessons = mysqli_query($conn, "SELECT * FROM lessons ORDER BY lesson_name ASC");
?>

<div class="card" style="background:#fff;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,.1);padding:20px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;border-bottom:2px solid #f0f0f0;padding-bottom:12px;">
        <h2 style="margin:0;color:#2c3e50;"><i class="fas fa-book"></i> Lesson Module</h2>
    </div>

    <?php if ($ok): ?><div style="padding:10px;background:#d4edda;color:#155724;border-radius:6px;margin-bottom:10px;"><?php echo htmlspecialchars($ok); ?></div><?php endif; ?>
    <?php if ($err): ?><div style="padding:10px;background:#f8d7da;color:#721c24;border-radius:6px;margin-bottom:10px;"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>

    <form method="post" style="display:flex;gap:10px;align-items:center;margin-bottom:15px;">
        <input type="text" name="lesson_name" required placeholder="Add lesson name" style="flex:1;padding:10px;border:1px solid #ddd;border-radius:6px;">
        <button type="submit" name="add_lesson" value="1" style="padding:10px 14px;background:#3498db;color:#fff;border:none;border-radius:6px;cursor:pointer;">
            <i class="fas fa-plus"></i> Add Lesson
        </button>
    </form>

    <div style="overflow:auto;">
        <table style="width:100%;border-collapse:collapse;">
            <thead>
            <tr>
                <th style="text-align:left;padding:10px;background:#34495e;color:#fff;">Lesson</th>
                <th style="text-align:left;padding:10px;background:#34495e;color:#fff;">Status</th>
                <th style="text-align:left;padding:10px;background:#34495e;color:#fff;">Action</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($lessons && mysqli_num_rows($lessons) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($lessons)): ?>
                    <tr>
                        <td style="padding:10px;border-bottom:1px solid #eee;"><?php echo htmlspecialchars($row['lesson_name']); ?></td>
                        <td style="padding:10px;border-bottom:1px solid #eee;"><?php echo htmlspecialchars($row['status']); ?></td>
                        <td style="padding:10px;border-bottom:1px solid #eee;">
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="lesson_id" value="<?php echo (int) $row['id']; ?>">
                                <input type="hidden" name="new_status" value="<?php echo $row['status'] === 'Active' ? 'Inactive' : 'Active'; ?>">
                                <button type="submit" name="toggle_lesson" value="1" style="padding:6px 10px;border:none;border-radius:5px;cursor:pointer;background:<?php echo $row['status'] === 'Active' ? '#f39c12' : '#27ae60'; ?>;color:#fff;">
                                    <?php echo $row['status'] === 'Active' ? 'Set Inactive' : 'Set Active'; ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="3" style="padding:10px;">No lessons yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
