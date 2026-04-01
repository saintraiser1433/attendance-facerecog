<?php
$success_message = '';
$error_message = '';
$generated_student_id = '';

// Create uploads directory if it doesn't exist
$upload_dir = '../uploads/student_ids/';
if (!file_exists($upload_dir)) {
    if (!mkdir($upload_dir, 0755, true)) {
        $error_message = 'Failed to create upload directory. Please check permissions.';
    }
} elseif (!is_writable($upload_dir)) {
    $error_message = 'Upload directory is not writable. Please check permissions.';
}

// Get the next student ID that will be generated
$year_prefix = date('Y');
$next_id_sql = "SELECT COALESCE(MAX(CAST(SUBSTRING(student_id, 6) AS UNSIGNED)), 0) + 1 as next_num 
                FROM students 
                WHERE student_id LIKE CONCAT(?, '-%')";
$next_id_stmt = mysqli_prepare($conn, $next_id_sql);
mysqli_stmt_bind_param($next_id_stmt, "s", $year_prefix);
mysqli_stmt_execute($next_id_stmt);
$next_id_result = mysqli_stmt_get_result($next_id_stmt);
$next_id_row = mysqli_fetch_assoc($next_id_result);
$next_student_id = $year_prefix . '-' . str_pad($next_id_row['next_num'], 2, '0', STR_PAD_LEFT);
mysqli_stmt_close($next_id_stmt);

// Get all year levels
$year_levels_sql = "SELECT * FROM year_levels WHERE status = 'Active' ORDER BY order_number ASC";
$year_levels_result = mysqli_query($conn, $year_levels_sql);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_student'])) {
    $first_name = mysqli_real_escape_string($conn, trim($_POST['first_name']));
    $last_name = mysqli_real_escape_string($conn, trim($_POST['last_name']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $date_of_birth = mysqli_real_escape_string($conn, $_POST['date_of_birth']);
    $address = mysqli_real_escape_string($conn, trim($_POST['address']));
    $year_level_id = !empty($_POST['year_level_id']) ? intval($_POST['year_level_id']) : NULL;
    $section = mysqli_real_escape_string($conn, trim($_POST['section']));
    $enrollment_date = mysqli_real_escape_string($conn, $_POST['enrollment_date']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    // Handle image upload with enhanced security
    $student_id_image = NULL;
    if (isset($_FILES['student_id_image']) && $_FILES['student_id_image']['error'] == UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $file_type = $_FILES['student_id_image']['type'];
        $file_size = $_FILES['student_id_image']['size'];
        $tmp_name = $_FILES['student_id_image']['tmp_name'];
        
        // Validate actual image content
        $image_info = getimagesize($tmp_name);
        if ($image_info === false) {
            $error_message = 'Uploaded file is not a valid image.';
        } elseif (in_array($file_type, $allowed_types) && $file_size <= 5000000) { // 5MB max
            $file_extension = pathinfo($_FILES['student_id_image']['name'], PATHINFO_EXTENSION);
            $new_filename = 'student_id_' . uniqid() . '.' . strtolower($file_extension);
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($tmp_name, $upload_path)) {
                $student_id_image = 'uploads/student_ids/' . $new_filename;
            } else {
                $error_message = 'Error uploading image file. Please check permissions.';
            }
        } else {
            $error_message = 'Invalid file type or size. Please upload JPG, PNG, or GIF under 5MB.';
        }
    } elseif (isset($_FILES['student_id_image']) && $_FILES['student_id_image']['error'] != UPLOAD_ERR_NO_FILE) {
        // Handle upload errors
        $upload_errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize in php.ini',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE in form',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'Upload stopped by extension'
        ];
        $error_message = $upload_errors[$_FILES['student_id_image']['error']] ?? 'Unknown upload error';
    }

    if (empty($error_message)) {
        if (empty($first_name) || empty($last_name) || empty($enrollment_date)) {
            $error_message = 'Please fill in all required fields.';
        } else {
            // Check if email already exists (if provided)
            if (!empty($email)) {
                $check_sql = "SELECT id FROM students WHERE email = ?";
                $stmt = mysqli_prepare($conn, $check_sql);
                mysqli_stmt_bind_param($stmt, "s", $email);
                mysqli_stmt_execute($stmt);
                $check_result = mysqli_stmt_get_result($stmt);
                
                if (mysqli_num_rows($check_result) > 0) {
                    $error_message = 'Email already exists.';
                    mysqli_stmt_close($stmt);
                } else {
                    mysqli_stmt_close($stmt);
                    // Insert new student
                    $insert_sql = "INSERT INTO students (first_name, last_name, email, phone, date_of_birth, address, year_level_id, section, student_id_image, enrollment_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = mysqli_prepare($conn, $insert_sql);
                    mysqli_stmt_bind_param($stmt, "ssssssissss", $first_name, $last_name, $email, $phone, $date_of_birth, $address, $year_level_id, $section, $student_id_image, $enrollment_date, $status);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        // Get the generated student_id
                        $last_id = mysqli_insert_id($conn);
                        $get_id_sql = "SELECT student_id FROM students WHERE id = ?";
                        $id_stmt = mysqli_prepare($conn, $get_id_sql);
                        mysqli_stmt_bind_param($id_stmt, "i", $last_id);
                        mysqli_stmt_execute($id_stmt);
                        $id_result = mysqli_stmt_get_result($id_stmt);
                        $id_row = mysqli_fetch_assoc($id_result);
                        $generated_student_id = $id_row['student_id'];
                        mysqli_stmt_close($id_stmt);
                        
                        $success_message = 'Student added successfully!';
                        $_POST = array();
                        
                        // Recalculate next ID
                        $next_id_result = mysqli_query($conn, $next_id_sql);
                        $next_id_row = mysqli_fetch_assoc($next_id_result);
                        $next_student_id = $year_prefix . '-' . str_pad($next_id_row['next_num'], 2, '0', STR_PAD_LEFT);
                    } else {
                        $error_message = 'Error adding student: ' . mysqli_error($conn);
                    }
                    mysqli_stmt_close($stmt);
                }
            } else {
                // No email provided, just insert
                $insert_sql = "INSERT INTO students (first_name, last_name, email, phone, date_of_birth, address, year_level_id, section, student_id_image, enrollment_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $insert_sql);
                mysqli_stmt_bind_param($stmt, "ssssssissss", $first_name, $last_name, $email, $phone, $date_of_birth, $address, $year_level_id, $section, $student_id_image, $enrollment_date, $status);
                
                if (mysqli_stmt_execute($stmt)) {
                    // Get the generated student_id
                    $last_id = mysqli_insert_id($conn);
                    $get_id_sql = "SELECT student_id FROM students WHERE id = ?";
                    $id_stmt = mysqli_prepare($conn, $get_id_sql);
                    mysqli_stmt_bind_param($id_stmt, "i", $last_id);
                    mysqli_stmt_execute($id_stmt);
                    $id_result = mysqli_stmt_get_result($id_stmt);
                    $id_row = mysqli_fetch_assoc($id_result);
                    $generated_student_id = $id_row['student_id'];
                    mysqli_stmt_close($id_stmt);
                    
                    $success_message = 'Student added successfully!';
                    $_POST = array();
                    
                    // Recalculate next ID
                    $next_id_result = mysqli_query($conn, $next_id_sql);
                    $next_id_row = mysqli_fetch_assoc($next_id_result);
                    $next_student_id = $year_prefix . '-' . str_pad($next_id_row['next_num'], 2, '0', STR_PAD_LEFT);
                } else {
                    $error_message = 'Error adding student: ' . mysqli_error($conn);
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}

// Get all students with year level info
$students_sql = "SELECT s.*, yl.year_level_name, yl.year_level_code 
                 FROM students s
                 LEFT JOIN year_levels yl ON s.year_level_id = yl.id
                 ORDER BY s.created_at DESC 
                 LIMIT 10";
$students_result = mysqli_query($conn, $students_sql);
?>

<style>
    .student-card {
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
    .file-upload-wrapper {
        position: relative;
        border: 2px dashed #3498db;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
        background: #f8f9fa;
        cursor: pointer;
        transition: all 0.3s;
    }
    .file-upload-wrapper:hover {
        background: #e9ecef;
        border-color: #2980b9;
    }
    .file-upload-wrapper input[type="file"] {
        position: absolute;
        opacity: 0;
        width: 100%;
        height: 100%;
        top: 0;
        left: 0;
        cursor: pointer;
    }
    .preview-image {
        max-width: 200px;
        max-height: 200px;
        margin-top: 10px;
        border-radius: 4px;
        border: 2px solid #ddd;
    }
    .btn {
        padding: 12px 30px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        transition: all 0.3s;
    }
    .btn-primary {
        background: #3498db;
        color: #fff;
    }
    .btn-primary:hover {
        background: #2980b9;
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
    .alert-info {
        background-color: #d1ecf1;
        color: #0c5460;
        border: 1px solid #bee5eb;
    }
    .student-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: 8px;
        overflow: hidden;
    }
    .student-table thead {
        background: #34495e;
        color: white;
    }
    .student-table th,
    .student-table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #ecf0f1;
    }
    .student-table tbody tr:hover {
        background: #f8f9fa;
    }
    .student-id-badge {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 6px 12px;
        border-radius: 4px;
        font-weight: 600;
        display: inline-block;
    }
    .year-level-badge {
        background: #3498db;
        color: white;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 0.85em;
        font-weight: 600;
    }
    .student-thumb {
        width: 40px;
        height: 40px;
        border-radius: 4px;
        object-fit: cover;
    }
</style>

<div class="student-card">
    <h2><i class="fas fa-user-graduate"></i> Add New Student</h2>
    <p>Complete student information with year level and ID image upload</p>

    <?php if ($success_message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            <?php if ($generated_student_id): ?>
                <br><strong>Generated Student ID: <?php echo $generated_student_id; ?></strong>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> <strong>Auto-Generated ID:</strong> The next student will receive ID: <strong><?php echo $next_student_id; ?></strong>
    </div>

    <form method="POST" action="" enctype="multipart/form-data">
        <div class="form-grid">
            <div class="form-group">
                <label>First Name *</label>
                <input type="text" name="first_name" class="form-control" required value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
            </div>
            <div class="form-group">
                <label>Last Name *</label>
                <input type="text" name="last_name" class="form-control" required value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone" class="form-control" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
            </div>
            <div class="form-group">
                <label>Date of Birth</label>
                <input type="date" name="date_of_birth" class="form-control" value="<?php echo isset($_POST['date_of_birth']) ? $_POST['date_of_birth'] : ''; ?>">
            </div>
            <div class="form-group">
                <label>Year Level *</label>
                <select name="year_level_id" class="form-control" required>
                    <option value="">Select Year Level</option>
                    <?php 
                    mysqli_data_seek($year_levels_result, 0);
                    while ($yl = mysqli_fetch_assoc($year_levels_result)): 
                    ?>
                        <option value="<?php echo $yl['id']; ?>" <?php echo (isset($_POST['year_level_id']) && $_POST['year_level_id'] == $yl['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($yl['year_level_name']); ?> (<?php echo htmlspecialchars($yl['year_level_code']); ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Section</label>
                <input type="text" name="section" class="form-control" placeholder="e.g., Section A" value="<?php echo isset($_POST['section']) ? htmlspecialchars($_POST['section']) : ''; ?>">
            </div>
            <div class="form-group">
                <label>Enrollment Date *</label>
                <input type="date" name="enrollment_date" class="form-control" required value="<?php echo isset($_POST['enrollment_date']) ? $_POST['enrollment_date'] : date('Y-m-d'); ?>">
            </div>
            <div class="form-group">
                <label>Status *</label>
                <select name="status" class="form-control" required>
                    <option value="Active" <?php echo (isset($_POST['status']) && $_POST['status'] == 'Active') ? 'selected' : 'selected'; ?>>Active</option>
                    <option value="Inactive" <?php echo (isset($_POST['status']) && $_POST['status'] == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                    <option value="Graduated" <?php echo (isset($_POST['status']) && $_POST['status'] == 'Graduated') ? 'selected' : ''; ?>>Graduated</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>Address</label>
            <textarea name="address" class="form-control" rows="2" placeholder="Complete address"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
        </div>

        <div class="form-group" style="margin-top:20px;">
            <label>Student ID Image (Optional)</label>
            <div class="file-upload-wrapper">
                <input type="file" name="student_id_image" id="student_id_image" accept="image/*" onchange="previewImage(this)">
                <div class="upload-text">
                    <i class="fas fa-cloud-upload-alt" style="font-size:3em;color:#3498db;margin-bottom:10px;"></i>
                    <p><strong>Click to upload</strong> or drag and drop</p>
                    <p style="font-size:0.85em;color:#7f8c8d;">JPG, PNG, or GIF (Max 5MB)</p>
                </div>
            </div>
            <div id="imagePreview" style="display:none;margin-top:15px;text-align:center;">
                <img id="preview" class="preview-image" src="" alt="Preview">
                <p style="margin-top:10px;"><button type="button" onclick="clearImage()" class="btn btn-danger btn-sm">Remove Image</button></p>
            </div>
        </div>

        <button type="submit" name="add_student" class="btn btn-primary" style="margin-top:20px;">
            <i class="fas fa-user-plus"></i> Add Student
        </button>
    </form>
</div>

<!-- Recently Added Students -->
<div class="student-card">
    <h3><i class="fas fa-history"></i> Recently Added Students</h3>
    <div style="overflow-x:auto;">
        <table class="student-table">
            <thead>
                <tr>
                    <th>ID Image</th>
                    <th>Student ID</th>
                    <th>Name</th>
                    <th>Year Level</th>
                    <th>Section</th>
                    <th>Email</th>
                    <th>Enrollment Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($students_result) > 0): ?>
                    <?php while ($student = mysqli_fetch_assoc($students_result)): ?>
                        <tr>
                            <td>
                                <?php if ($student['student_id_image']): ?>
                                    <img src="../<?php echo htmlspecialchars($student['student_id_image']); ?>" class="student-thumb" alt="ID">
                                <?php else: ?>
                                    <div style="width:40px;height:40px;background:#ecf0f1;border-radius:4px;display:flex;align-items:center;justify-content:center;">
                                        <i class="fas fa-user" style="color:#95a5a6;"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><span class="student-id-badge"><?php echo htmlspecialchars($student['student_id']); ?></span></td>
                            <td><strong><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></strong></td>
                            <td>
                                <?php if ($student['year_level_name']): ?>
                                    <span class="year-level-badge"><?php echo htmlspecialchars($student['year_level_name']); ?></span>
                                <?php else: ?>
                                    <span style="color:#95a5a6;">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($student['section'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($student['email'] ?? '-'); ?></td>
                            <td><?php echo date('M d, Y', strtotime($student['enrollment_date'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align:center;padding:40px;color:#7f8c8d;">
                            No students added yet.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('preview').src = e.target.result;
            document.getElementById('imagePreview').style.display = 'block';
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function clearImage() {
    document.getElementById('student_id_image').value = '';
    document.getElementById('imagePreview').style.display = 'none';
    document.getElementById('preview').src = '';
}
</script>
