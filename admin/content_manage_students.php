<?php
// Handle delete action
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $delete_sql = "DELETE FROM students WHERE id = ?";
    $stmt = mysqli_prepare($conn, $delete_sql);
    mysqli_stmt_bind_param($stmt, "i", $delete_id);
    if (mysqli_stmt_execute($stmt)) {
        echo '<div class="alert alert-success">Student deleted successfully!</div>';
    } else {
        echo '<div class="alert alert-danger">Error deleting student: ' . mysqli_error($conn) . '</div>';
    }
    mysqli_stmt_close($stmt);
}

// Handle status update
if (isset($_POST['update_status'])) {
    $student_id = intval($_POST['student_id']);
    $new_status = $_POST['status'];
    $update_sql = "UPDATE students SET status = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($stmt, "si", $new_status, $student_id);
    if (mysqli_stmt_execute($stmt)) {
        echo '<div class="alert alert-success">Status updated successfully!</div>';
    } else {
        echo '<div class="alert alert-danger">Error updating status: ' . mysqli_error($conn) . '</div>';
    }
    mysqli_stmt_close($stmt);
}

// Fetch all students with their year level and assigned tutor info
$sql = "SELECT s.*, 
               yl.year_level_name,
               GROUP_CONCAT(DISTINCT CONCAT(t.first_name, ' ', t.last_name) SEPARATOR ', ') as tutors
        FROM students s
        LEFT JOIN year_levels yl ON s.year_level_id = yl.id
        LEFT JOIN tutor_student_matching tsm ON s.id = tsm.student_id AND tsm.status = 'Active'
        LEFT JOIN tutors t ON tsm.tutor_id = t.id
        GROUP BY s.id
        ORDER BY s.created_at DESC";
$result = mysqli_query($conn, $sql);
?>

<style>
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
    .card {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        padding: 20px;
        margin-bottom: 20px;
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
    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s;
    }
    .btn-primary {
        background: #3498db;
        color: #fff;
    }
    .btn-primary:hover {
        background: #2980b9;
        color: #fff;
    }
    .btn-danger {
        background: #e74c3c;
        color: #fff;
    }
    .btn-danger:hover {
        background: #c0392b;
    }
    .btn-warning {
        background: #f39c12;
        color: #fff;
    }
    .btn-warning:hover {
        background: #e67e22;
    }
    .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
    }
    .table-container {
        overflow-x: auto;
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
        text-transform: uppercase;
        font-size: 0.85em;
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
    .badge-graduated {
        background: #d1ecf1;
        color: #0c5460;
    }
    .search-box {
        margin-bottom: 20px;
    }
    .search-box input {
        width: 100%;
        max-width: 400px;
        padding: 10px 15px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
    }
    .action-buttons {
        display: flex;
        gap: 5px;
    }
    .student-image {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
    }
    .stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }
    .stat-box {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #fff;
        padding: 20px;
        border-radius: 8px;
        text-align: center;
    }
    .stat-box h3 {
        margin: 0;
        font-size: 2em;
    }
    .stat-box p {
        margin: 5px 0 0;
        opacity: 0.9;
    }
</style>

<div class="card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-user-graduate"></i> Manage Students</h2>
        <a href="?page=input_students" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Student
        </a>
    </div>

    <?php
    // Get statistics
    $total_students = mysqli_num_rows($result);
    $active_students_sql = "SELECT COUNT(*) as count FROM students WHERE status = 'Active'";
    $active_count = mysqli_fetch_assoc(mysqli_query($conn, $active_students_sql))['count'] ?? 0;
    $with_tutors_sql = "SELECT COUNT(DISTINCT student_id) as count FROM tutor_student_matching WHERE status = 'Active'";
    $with_tutors = mysqli_fetch_assoc(mysqli_query($conn, $with_tutors_sql))['count'] ?? 0;
    ?>

    <div class="stats-row">
        <div class="stat-box">
            <h3><?php echo $total_students; ?></h3>
            <p>Total Students</p>
        </div>
        <div class="stat-box" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
            <h3><?php echo $active_count; ?></h3>
            <p>Active Students</p>
        </div>
        <div class="stat-box" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
            <h3><?php echo $with_tutors; ?></h3>
            <p>With Assigned Tutors</p>
        </div>
    </div>

    <div class="search-box">
        <input type="text" id="searchInput" placeholder="Search students by name, email, student ID, or year level..." onkeyup="searchTable()">
    </div>

    <div class="table-container">
        <table id="studentsTable">
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Year Level</th>
                    <th>Section</th>
                    <th>Assigned Tutors</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                mysqli_data_seek($result, 0); // Reset result pointer
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
                            case 'Graduated':
                                $status_class = 'badge-graduated';
                                break;
                        }
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($row['student_id']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['email'] ?? 'N/A') . '</td>';
                        echo '<td>' . htmlspecialchars($row['phone'] ?? 'N/A') . '</td>';
                        echo '<td>' . htmlspecialchars($row['year_level_name'] ?? 'N/A') . '</td>';
                        echo '<td>' . htmlspecialchars($row['section'] ?? 'N/A') . '</td>';
                        echo '<td>' . htmlspecialchars($row['tutors'] ?? 'None') . '</td>';
                        echo '<td><span class="badge ' . $status_class . '">' . htmlspecialchars($row['status']) . '</span></td>';
                        echo '<td class="action-buttons">';
                        echo '<a href="?page=edit_student&id=' . $row['id'] . '" class="btn btn-warning btn-sm" title="Edit"><i class="fas fa-edit"></i></a>';
                        echo '<a href="?page=manage_students&delete_id=' . $row['id'] . '" class="btn btn-danger btn-sm" onclick="return confirm(\'Are you sure you want to delete this student?\')" title="Delete"><i class="fas fa-trash"></i></a>';
                        echo '</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="9" style="text-align:center;">No students found. <a href="?page=input_students">Add your first student</a></td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function searchTable() {
    var input = document.getElementById("searchInput");
    var filter = input.value.toUpperCase();
    var table = document.getElementById("studentsTable");
    var tr = table.getElementsByTagName("tr");

    for (var i = 1; i < tr.length; i++) {
        var td = tr[i].getElementsByTagName("td");
        var found = false;
        
        for (var j = 0; j < td.length - 1; j++) {
            if (td[j]) {
                var txtValue = td[j].textContent || td[j].innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    found = true;
                    break;
                }
            }
        }
        
        if (found) {
            tr[i].style.display = "";
        } else {
            tr[i].style.display = "none";
        }
    }
}
</script>
