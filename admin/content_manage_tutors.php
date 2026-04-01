<?php
// Handle delete action
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $delete_sql = "DELETE FROM tutors WHERE id = ?";
    $stmt = mysqli_prepare($conn, $delete_sql);
    mysqli_stmt_bind_param($stmt, "i", $delete_id);
    if (mysqli_stmt_execute($stmt)) {
        echo '<div class="alert alert-success">Tutor deleted successfully!</div>';
    } else {
        echo '<div class="alert alert-danger">Error deleting tutor: ' . mysqli_error($conn) . '</div>';
    }
    mysqli_stmt_close($stmt);
}

// Handle status update
if (isset($_POST['update_status'])) {
    $tutor_id = intval($_POST['tutor_id']);
    $new_status = $_POST['status'];
    $update_sql = "UPDATE tutors SET status = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($stmt, "si", $new_status, $tutor_id);
    if (mysqli_stmt_execute($stmt)) {
        echo '<div class="alert alert-success">Status updated successfully!</div>';
    } else {
        echo '<div class="alert alert-danger">Error updating status: ' . mysqli_error($conn) . '</div>';
    }
    mysqli_stmt_close($stmt);
}

// Fetch all tutors
$sql = "SELECT * FROM tutors ORDER BY created_at DESC";
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
    .badge-leave {
        background: #fff3cd;
        color: #856404;
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
</style>

<div class="card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-chalkboard-teacher"></i> Manage Tutors</h2>
        <a href="?page=add_tutor" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Tutor
        </a>
    </div>

    <div class="search-box">
        <input type="text" id="searchInput" placeholder="Search tutors by name, email, or specialization..." onkeyup="searchTable()">
    </div>

    <div class="table-container">
        <table id="tutorsTable">
            <thead>
                <tr>
                    <th>Tutor ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Specialization</th>
                    <th>Experience</th>
                    <th>Hourly Rate</th>
                    <th>Status</th>
                    <th>Actions</th>
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
                            case 'On Leave':
                                $status_class = 'badge-leave';
                                break;
                        }
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($row['tutor_id']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['email']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['phone'] ?? 'N/A') . '</td>';
                        echo '<td>' . htmlspecialchars($row['specialization'] ?? 'N/A') . '</td>';
                        echo '<td>' . htmlspecialchars($row['experience_years']) . ' years</td>';
                        echo '<td>$' . number_format($row['hourly_rate'], 2) . '</td>';
                        echo '<td><span class="badge ' . $status_class . '">' . htmlspecialchars($row['status']) . '</span></td>';
                        echo '<td class="action-buttons">';
                        echo '<a href="?page=edit_tutor&id=' . $row['id'] . '" class="btn btn-warning btn-sm" title="Edit"><i class="fas fa-edit"></i></a>';
                        echo '<a href="?page=manage_tutors&delete_id=' . $row['id'] . '" class="btn btn-danger btn-sm" onclick="return confirm(\'Are you sure you want to delete this tutor?\')" title="Delete"><i class="fas fa-trash"></i></a>';
                        echo '</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="9" style="text-align:center;">No tutors found. <a href="?page=add_tutor">Add your first tutor</a></td></tr>';
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
    var table = document.getElementById("tutorsTable");
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
