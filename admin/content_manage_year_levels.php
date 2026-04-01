<?php
// Manage Year Levels
$success_message = '';
$error_message = '';

// Handle Add Year Level
if (isset($_POST['add_year_level'])) {
    $year_code = mysqli_real_escape_string($conn, $_POST['year_level_code']);
    $year_name = mysqli_real_escape_string($conn, $_POST['year_level_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $order_number = intval($_POST['order_number']);
    
    $sql = "INSERT INTO year_levels (year_level_code, year_level_name, description, order_number) 
            VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sssi", $year_code, $year_name, $description, $order_number);
    
    if (mysqli_stmt_execute($stmt)) {
        $success_message = "Year level added successfully!";
    } else {
        $error_message = "Error adding year level: " . mysqli_error($conn);
    }
    mysqli_stmt_close($stmt);
}

// Handle Update Year Level
if (isset($_POST['update_year_level'])) {
    $id = intval($_POST['year_level_id']);
    $year_code = mysqli_real_escape_string($conn, $_POST['year_level_code']);
    $year_name = mysqli_real_escape_string($conn, $_POST['year_level_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $order_number = intval($_POST['order_number']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    $sql = "UPDATE year_levels SET year_level_code=?, year_level_name=?, description=?, order_number=?, status=? WHERE id=?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sssisi", $year_code, $year_name, $description, $order_number, $status, $id);
    
    if (mysqli_stmt_execute($stmt)) {
        $success_message = "Year level updated successfully!";
    } else {
        $error_message = "Error updating year level: " . mysqli_error($conn);
    }
    mysqli_stmt_close($stmt);
}

// Handle Delete Year Level
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    
    // Check if students table has year_level_id column before checking usage
    $column_check_del = mysqli_query($conn, "SHOW COLUMNS FROM students LIKE 'year_level_id'");
    $has_column = mysqli_num_rows($column_check_del) > 0;
    
    $can_delete = true;
    if ($has_column) {
        // Check if year level is being used
        $check_sql = "SELECT COUNT(*) as count FROM students WHERE year_level_id = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "i", $id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        $check_row = mysqli_fetch_assoc($check_result);
        
        if ($check_row['count'] > 0) {
            $error_message = "Cannot delete year level. It is currently assigned to " . $check_row['count'] . " student(s).";
            $can_delete = false;
        }
        mysqli_stmt_close($check_stmt);
    }
    
    if ($can_delete) {
        $sql = "DELETE FROM year_levels WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        
        if (mysqli_stmt_execute($stmt)) {
            $success_message = "Year level deleted successfully!";
        } else {
            $error_message = "Error deleting year level: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    }
}

// Check if students table has year_level_id column
$column_check = mysqli_query($conn, "SHOW COLUMNS FROM students LIKE 'year_level_id'");
$has_year_level_column = mysqli_num_rows($column_check) > 0;

// Get all year levels with conditional student count
if ($has_year_level_column) {
    $year_levels_sql = "SELECT yl.*, 
                        (SELECT COUNT(*) FROM students WHERE year_level_id = yl.id) as student_count
                        FROM year_levels yl
                        ORDER BY yl.order_number ASC";
} else {
    // If column doesn't exist yet, just get year levels without student count
    $year_levels_sql = "SELECT yl.*, 0 as student_count
                        FROM year_levels yl
                        ORDER BY yl.order_number ASC";
}
$year_levels_result = mysqli_query($conn, $year_levels_sql);
?>

<style>
    .year-level-card {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        padding: 30px;
        margin-bottom: 30px;
    }
    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
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
    .form-control:focus {
        outline: none;
        border-color: #3498db;
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
    .btn-warning {
        background: #f39c12;
        color: #fff;
    }
    .btn-danger {
        background: #e74c3c;
        color: #fff;
    }
    .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
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
    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    .year-level-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: 8px;
        overflow: hidden;
    }
    .year-level-table thead {
        background: #34495e;
        color: white;
    }
    .year-level-table th,
    .year-level-table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #ecf0f1;
    }
    .year-level-table tbody tr:hover {
        background: #f8f9fa;
    }
    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 0.85em;
        font-weight: bold;
    }
    .status-active {
        background: #d4edda;
        color: #155724;
    }
    .status-inactive {
        background: #f8d7da;
        color: #721c24;
    }
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
    }
    .modal-content {
        background-color: #fefefe;
        margin: 5% auto;
        padding: 30px;
        border-radius: 8px;
        width: 80%;
        max-width: 600px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    .close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }
    .close:hover {
        color: #000;
    }
</style>

<div class="year-level-card">
    <h2><i class="fas fa-layer-group"></i> Manage Year Levels</h2>
    <p>Organize students by academic year levels for better management</p>

    <?php if ($success_message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <!-- Add Year Level Form -->
    <div style="background:#f8f9fa;padding:20px;border-radius:8px;margin-bottom:30px;">
        <h3><i class="fas fa-plus-circle"></i> Add New Year Level</h3>
        <form method="POST" action="">
            <div class="form-grid">
                <div class="form-group">
                    <label>Year Level Code *</label>
                    <input type="text" name="year_level_code" class="form-control" placeholder="e.g., YR1" required>
                </div>
                <div class="form-group">
                    <label>Year Level Name *</label>
                    <input type="text" name="year_level_name" class="form-control" placeholder="e.g., Year 1" required>
                </div>
                <div class="form-group">
                    <label>Order Number *</label>
                    <input type="number" name="order_number" class="form-control" placeholder="e.g., 1" required>
                </div>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control" rows="2" placeholder="Brief description of this year level"></textarea>
            </div>
            <button type="submit" name="add_year_level" class="btn btn-success" style="margin-top:15px;">
                <i class="fas fa-plus"></i> Add Year Level
            </button>
        </form>
    </div>

    <!-- Year Levels Table -->
    <h3><i class="fas fa-list"></i> Existing Year Levels</h3>
    <div style="overflow-x:auto;">
        <table class="year-level-table">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Code</th>
                    <th>Year Level Name</th>
                    <th>Description</th>
                    <th>Students</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($year_levels_result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($year_levels_result)): ?>
                        <tr>
                            <td><strong><?php echo $row['order_number']; ?></strong></td>
                            <td><span style="background:#3498db;color:white;padding:4px 8px;border-radius:4px;font-weight:600;"><?php echo htmlspecialchars($row['year_level_code']); ?></span></td>
                            <td><strong><?php echo htmlspecialchars($row['year_level_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['description'] ?? '-'); ?></td>
                            <td>
                                <span style="background:#27ae60;color:white;padding:4px 12px;border-radius:12px;font-weight:600;">
                                    <i class="fas fa-users"></i> <?php echo $row['student_count']; ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($row['status']); ?>">
                                    <?php echo $row['status']; ?>
                                </span>
                            </td>
                            <td>
                                <button onclick="editYearLevel(<?php echo htmlspecialchars(json_encode($row)); ?>)" class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <?php if ($row['student_count'] == 0): ?>
                                    <a href="?page=manage_year_levels&delete_id=<?php echo $row['id']; ?>" 
                                       onclick="return confirm('Are you sure you want to delete this year level?')" 
                                       class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align:center;padding:40px;color:#7f8c8d;">
                            <i class="fas fa-inbox" style="font-size:3em;margin-bottom:15px;color:#bdc3c7;"></i>
                            <p>No year levels found. Add your first year level above.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h3><i class="fas fa-edit"></i> Edit Year Level</h3>
        <form method="POST" action="">
            <input type="hidden" name="year_level_id" id="edit_id">
            <div class="form-grid">
                <div class="form-group">
                    <label>Year Level Code *</label>
                    <input type="text" name="year_level_code" id="edit_code" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Year Level Name *</label>
                    <input type="text" name="year_level_name" id="edit_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Order Number *</label>
                    <input type="number" name="order_number" id="edit_order" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Status *</label>
                    <select name="status" id="edit_status" class="form-control" required>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" id="edit_description" class="form-control" rows="2"></textarea>
            </div>
            <button type="submit" name="update_year_level" class="btn btn-primary" style="margin-top:15px;">
                <i class="fas fa-save"></i> Update Year Level
            </button>
        </form>
    </div>
</div>

<script>
function editYearLevel(data) {
    document.getElementById('edit_id').value = data.id;
    document.getElementById('edit_code').value = data.year_level_code;
    document.getElementById('edit_name').value = data.year_level_name;
    document.getElementById('edit_order').value = data.order_number;
    document.getElementById('edit_status').value = data.status;
    document.getElementById('edit_description').value = data.description || '';
    document.getElementById('editModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    var modal = document.getElementById('editModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}
</script>
