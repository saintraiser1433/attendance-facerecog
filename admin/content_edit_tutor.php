<?php
$success_message = '';
$error_message = '';
$tutor = null;

// Get tutor ID from URL
if (!isset($_GET['id'])) {
    header("Location: ?page=manage_tutors");
    exit();
}

$tutor_id = intval($_GET['id']);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_tutor'])) {
    // Sanitize and validate input
    $tutor_id_code = mysqli_real_escape_string($conn, trim($_POST['tutor_id']));
    $first_name = mysqli_real_escape_string($conn, trim($_POST['first_name']));
    $last_name = mysqli_real_escape_string($conn, trim($_POST['last_name']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $specialization = mysqli_real_escape_string($conn, trim($_POST['specialization']));
    $qualification = mysqli_real_escape_string($conn, trim($_POST['qualification']));
    $experience_years = intval($_POST['experience_years']);
    $hourly_rate = floatval($_POST['hourly_rate']);
    $address = mysqli_real_escape_string($conn, trim($_POST['address']));
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $hire_date = mysqli_real_escape_string($conn, $_POST['hire_date']);

    // Validate required fields
    if (empty($tutor_id_code) || empty($first_name) || empty($last_name) || empty($email) || empty($hire_date)) {
        $error_message = 'Please fill in all required fields.';
    } else {
        // Check if tutor_id or email already exists for other tutors
        $check_sql = "SELECT id FROM tutors WHERE (tutor_id = ? OR email = ?) AND id != ?";
        $stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($stmt, "ssi", $tutor_id_code, $email, $tutor_id);
        mysqli_stmt_execute($stmt);
        $check_result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error_message = 'Tutor ID or Email already exists for another tutor.';
        } else {
            // Update tutor
            $update_sql = "UPDATE tutors SET tutor_id = ?, first_name = ?, last_name = ?, email = ?, phone = ?, specialization = ?, qualification = ?, experience_years = ?, hourly_rate = ?, address = ?, status = ?, hire_date = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($stmt, "sssssssissssi", $tutor_id_code, $first_name, $last_name, $email, $phone, $specialization, $qualification, $experience_years, $hourly_rate, $address, $status, $hire_date, $tutor_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $success_message = 'Tutor updated successfully!';
            } else {
                $error_message = 'Error updating tutor: ' . mysqli_error($conn);
            }
        }
        mysqli_stmt_close($stmt);
    }
}

// Fetch tutor data
$sql = "SELECT * FROM tutors WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $tutor_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 1) {
    $tutor = mysqli_fetch_assoc($result);
} else {
    header("Location: ?page=manage_tutors");
    exit();
}
mysqli_stmt_close($stmt);
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
        padding: 30px;
        max-width: 800px;
        margin: 0 auto;
    }
    .card-header {
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f0f0f0;
    }
    .card-title {
        font-size: 1.8em;
        font-weight: bold;
        color: #2c3e50;
        margin: 0;
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
    .form-group label .required {
        color: #e74c3c;
    }
    .form-control {
        width: 100%;
        padding: 10px 15px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
        transition: border-color 0.3s;
    }
    .form-control:focus {
        outline: none;
        border-color: #3498db;
    }
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
    .btn {
        padding: 12px 30px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s;
        margin-right: 10px;
    }
    .btn-primary {
        background: #3498db;
        color: #fff;
    }
    .btn-primary:hover {
        background: #2980b9;
    }
    .btn-secondary {
        background: #95a5a6;
        color: #fff;
    }
    .btn-secondary:hover {
        background: #7f8c8d;
    }
    .form-actions {
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid #f0f0f0;
    }
    textarea.form-control {
        min-height: 100px;
        resize: vertical;
    }
    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-edit"></i> Edit Tutor</h2>
    </div>

    <?php if ($success_message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            <a href="?page=manage_tutors">View all tutors</a>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-row">
            <div class="form-group">
                <label for="tutor_id">Tutor ID <span class="required">*</span></label>
                <input type="text" class="form-control" id="tutor_id" name="tutor_id" 
                       value="<?php echo htmlspecialchars($tutor['tutor_id']); ?>" required>
            </div>
            <div class="form-group">
                <label for="hire_date">Hire Date <span class="required">*</span></label>
                <input type="date" class="form-control" id="hire_date" name="hire_date" 
                       value="<?php echo htmlspecialchars($tutor['hire_date']); ?>" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="first_name">First Name <span class="required">*</span></label>
                <input type="text" class="form-control" id="first_name" name="first_name" 
                       value="<?php echo htmlspecialchars($tutor['first_name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="last_name">Last Name <span class="required">*</span></label>
                <input type="text" class="form-control" id="last_name" name="last_name" 
                       value="<?php echo htmlspecialchars($tutor['last_name']); ?>" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="email">Email <span class="required">*</span></label>
                <input type="email" class="form-control" id="email" name="email" 
                       value="<?php echo htmlspecialchars($tutor['email']); ?>" required>
            </div>
            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="tel" class="form-control" id="phone" name="phone" 
                       value="<?php echo htmlspecialchars($tutor['phone'] ?? ''); ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="specialization">Specialization</label>
                <input type="text" class="form-control" id="specialization" name="specialization" 
                       value="<?php echo htmlspecialchars($tutor['specialization'] ?? ''); ?>" 
                       placeholder="e.g., Mathematics, Physics, English">
            </div>
            <div class="form-group">
                <label for="qualification">Qualification</label>
                <input type="text" class="form-control" id="qualification" name="qualification" 
                       value="<?php echo htmlspecialchars($tutor['qualification'] ?? ''); ?>" 
                       placeholder="e.g., PhD, Masters, Bachelor's">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="experience_years">Experience (Years)</label>
                <input type="number" class="form-control" id="experience_years" name="experience_years" 
                       value="<?php echo htmlspecialchars($tutor['experience_years']); ?>" 
                       min="0" max="50">
            </div>
            <div class="form-group">
                <label for="hourly_rate">Hourly Rate ($)</label>
                <input type="number" class="form-control" id="hourly_rate" name="hourly_rate" 
                       value="<?php echo htmlspecialchars($tutor['hourly_rate']); ?>" 
                       min="0" step="0.01">
            </div>
        </div>

        <div class="form-group">
            <label for="status">Status</label>
            <select class="form-control" id="status" name="status">
                <option value="Active" <?php echo ($tutor['status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                <option value="Inactive" <?php echo ($tutor['status'] == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                <option value="On Leave" <?php echo ($tutor['status'] == 'On Leave') ? 'selected' : ''; ?>>On Leave</option>
            </select>
        </div>

        <div class="form-group">
            <label for="address">Address</label>
            <textarea class="form-control" id="address" name="address"><?php echo htmlspecialchars($tutor['address'] ?? ''); ?></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" name="update_tutor" class="btn btn-primary">
                <i class="fas fa-save"></i> Update Tutor
            </button>
            <a href="?page=manage_tutors" class="btn btn-secondary">
                <i class="fas fa-times"></i> Cancel
            </a>
        </div>
    </form>
</div>
