<?php
require_once __DIR__ . '/../includes/sms_schema.php';
require_once __DIR__ . '/../includes/ph_phone.php';
sms_ensure_schema($conn);

$success_message = '';
$error_message = '';
$generated_student_id = '';

// Create uploads directory if it doesn't exist
$upload_dir = '../uploads/student_ids/';
if (!file_exists($upload_dir)) {
    if (!mkdir($upload_dir, 0755, true)) {
        $error_message = 'Failed to create upload directory. Please check permissions.';
    }
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

// Get all active tutors
$tutors_sql = "SELECT id, tutor_id, first_name, last_name, specialization FROM tutors WHERE status = 'Active' ORDER BY first_name ASC";
$tutors_result = mysqli_query($conn, $tutors_sql);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_student'])) {
    $first_name = mysqli_real_escape_string($conn, trim($_POST['first_name']));
    $last_name = mysqli_real_escape_string($conn, trim($_POST['last_name']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $phone_raw = trim($_POST['phone'] ?? '');
    $emergency_contact_name = mysqli_real_escape_string($conn, trim($_POST['emergency_contact_name'] ?? ''));
    $emergency_phone_raw = trim($_POST['emergency_contact_phone'] ?? '');
    $date_of_birth = mysqli_real_escape_string($conn, $_POST['date_of_birth']);
    $address = mysqli_real_escape_string($conn, trim($_POST['address']));
    $year_level_id = !empty($_POST['year_level_id']) ? intval($_POST['year_level_id']) : NULL;
    $section = mysqli_real_escape_string($conn, trim($_POST['section']));
    $enrollment_date = mysqli_real_escape_string($conn, $_POST['enrollment_date']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $assigned_tutor_id = !empty($_POST['assigned_tutor_id']) ? intval($_POST['assigned_tutor_id']) : NULL;
    $tutor_subject = mysqli_real_escape_string($conn, trim($_POST['tutor_subject']));
    
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
    }

    $phone = null;
    if ($phone_raw !== '') {
        $phone = validate_ph_mobile_required($phone_raw);
        if ($phone === null) {
            $error_message = 'Phone must be a valid Philippine mobile number (saved as +639XXXXXXXXX).';
        }
    }
    $emergency_contact_phone = null;
    if ($emergency_phone_raw !== '') {
        $emergency_contact_phone = validate_ph_mobile_required($emergency_phone_raw);
        if ($emergency_contact_phone === null) {
            $error_message = 'Emergency contact number must be a valid Philippine mobile (+639XXXXXXXXX).';
        }
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
                }
            }
            
            if (empty($error_message)) {
                // Insert new student
                $insert_sql = "INSERT INTO students (first_name, last_name, email, phone, emergency_contact_name, emergency_contact_phone, date_of_birth, address, year_level_id, section, student_id_image, enrollment_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $insert_sql);
                $phone_bind = $phone ?? '';
                $em_name_bind = $emergency_contact_name;
                $em_phone_bind = $emergency_contact_phone ?? '';
                mysqli_stmt_bind_param($stmt, "ssssssssissss", $first_name, $last_name, $email, $phone_bind, $em_name_bind, $em_phone_bind, $date_of_birth, $address, $year_level_id, $section, $student_id_image, $enrollment_date, $status);
                
                if (mysqli_stmt_execute($stmt)) {
                    $last_student_id = mysqli_insert_id($conn);
                    
                    // Get the generated student_id
                    $get_id_sql = "SELECT student_id FROM students WHERE id = ?";
                    $id_stmt = mysqli_prepare($conn, $get_id_sql);
                    mysqli_stmt_bind_param($id_stmt, "i", $last_student_id);
                    mysqli_stmt_execute($id_stmt);
                    $id_result = mysqli_stmt_get_result($id_stmt);
                    $id_row = mysqli_fetch_assoc($id_result);
                    $generated_student_id = $id_row['student_id'];
                    mysqli_stmt_close($id_stmt);
                    
                    // Assign tutor if selected
                    if ($assigned_tutor_id && !empty($tutor_subject)) {
                        $match_sql = "INSERT INTO tutor_student_matching (tutor_id, student_id, subject, status, start_date) VALUES (?, ?, ?, 'Active', CURDATE())";
                        $match_stmt = mysqli_prepare($conn, $match_sql);
                        mysqli_stmt_bind_param($match_stmt, "iis", $assigned_tutor_id, $last_student_id, $tutor_subject);
                        mysqli_stmt_execute($match_stmt);
                        mysqli_stmt_close($match_stmt);
                    }
                    
                    $success_message = 'Student added successfully!';
                    $_POST = array();
                    
                    // Recalculate next ID
                    $next_id_stmt = mysqli_prepare($conn, $next_id_sql);
                    mysqli_stmt_bind_param($next_id_stmt, "s", $year_prefix);
                    mysqli_stmt_execute($next_id_stmt);
                    $next_id_result = mysqli_stmt_get_result($next_id_stmt);
                    $next_id_row = mysqli_fetch_assoc($next_id_result);
                    $next_student_id = $year_prefix . '-' . str_pad($next_id_row['next_num'], 2, '0', STR_PAD_LEFT);
                    mysqli_stmt_close($next_id_stmt);
                } else {
                    $error_message = 'Error adding student: ' . mysqli_error($conn);
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}
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
        max-width: 900px;
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
    .form-row-3 {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
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
    .form-actions {
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid #f0f0f0;
    }
    textarea.form-control {
        min-height: 100px;
        resize: vertical;
    }
    .upload-area {
        border: 2px dashed #ddd;
        border-radius: 8px;
        padding: 30px;
        text-align: center;
        background: #f8f9fa;
        transition: all 0.3s;
        cursor: pointer;
    }
    .upload-area:hover {
        border-color: #3498db;
        background: #e3f2fd;
    }
    .upload-area.dragover {
        border-color: #2980b9;
        background: #bbdefb;
    }
    .upload-icon {
        font-size: 48px;
        color: #3498db;
        margin-bottom: 15px;
    }
    .image-preview {
        max-width: 200px;
        max-height: 200px;
        margin: 15px auto;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .section-divider {
        margin: 30px 0;
        padding: 15px;
        background: #f8f9fa;
        border-left: 4px solid #3498db;
        border-radius: 4px;
    }
    .section-divider h3 {
        margin: 0;
        color: #2c3e50;
        font-size: 1.2em;
    }
    @media (max-width: 768px) {
        .form-row, .form-row-3 {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="card">
    <div class="card-header">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2 class="card-title"><i class="fas fa-user-graduate"></i> Add New Student</h2>
            <a href="../student_registration.php" target="_blank" class="btn" style="background: #27ae60; color: white; padding: 10px 20px; font-size: 13px;">
                <i class="fas fa-external-link-alt"></i> Public Registration Form
            </a>
        </div>
        <p style="margin: 10px 0 0 0; color: #666; font-size: 14px;">
            <i class="fas fa-info-circle"></i> Students can also self-register using the public registration form
        </p>
    </div>

    <?php if ($success_message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            <?php if ($generated_student_id): ?>
                <br><strong>Generated Student ID: <span style="font-size:1.2em;color:#155724;"><?php echo htmlspecialchars($generated_student_id); ?></span></strong>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="" enctype="multipart/form-data">
        <div class="alert" style="background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb;">
            <i class="fas fa-info-circle"></i> <strong>Auto-Generated ID:</strong> The next student will receive ID: <strong style="font-size:1.1em;"><?php echo htmlspecialchars($next_student_id); ?></strong>
        </div>

        <!-- Basic Information -->
        <div class="section-divider">
            <h3><i class="fas fa-user"></i> Basic Information</h3>
        </div>

        <div class="form-group">
            <label for="enrollment_date">Enrollment Date <span class="required">*</span></label>
            <input type="date" class="form-control" id="enrollment_date" name="enrollment_date" 
                   value="<?php echo isset($_POST['enrollment_date']) ? htmlspecialchars($_POST['enrollment_date']) : ''; ?>" required>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="first_name">First Name <span class="required">*</span></label>
                <input type="text" class="form-control" id="first_name" name="first_name" 
                       value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>" required>
            </div>
            <div class="form-group">
                <label for="last_name">Last Name <span class="required">*</span></label>
                <input type="text" class="form-control" id="last_name" name="last_name" 
                       value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" class="form-control" id="email" name="email" 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            <div class="form-group">
                <label for="phone">Phone <small style="font-weight:normal;color:#666;">(Philippine mobile, stored as +639…)</small></label>
                <input type="tel" class="form-control" id="phone" name="phone" inputmode="numeric" autocomplete="tel"
                       placeholder="9123456789"
                       value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
            </div>
        </div>

        <div class="section-divider">
            <h3><i class="fas fa-phone-alt"></i> In case of emergency</h3>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="emergency_contact_name">Emergency contact name</label>
                <input type="text" class="form-control" id="emergency_contact_name" name="emergency_contact_name"
                       value="<?php echo isset($_POST['emergency_contact_name']) ? htmlspecialchars($_POST['emergency_contact_name']) : ''; ?>"
                       placeholder="Parent / guardian name">
            </div>
            <div class="form-group">
                <label for="emergency_contact_phone">Emergency contact number</label>
                <input type="tel" class="form-control" id="emergency_contact_phone" name="emergency_contact_phone" inputmode="numeric" autocomplete="tel"
                       placeholder="9123456789 (+639 required if provided)"
                       value="<?php echo isset($_POST['emergency_contact_phone']) ? htmlspecialchars($_POST['emergency_contact_phone']) : ''; ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="date_of_birth">Date of Birth</label>
                <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" 
                       value="<?php echo isset($_POST['date_of_birth']) ? htmlspecialchars($_POST['date_of_birth']) : ''; ?>">
            </div>
            <div class="form-group">
                <label for="status">Status</label>
                <select class="form-control" id="status" name="status">
                    <option value="Active" <?php echo (isset($_POST['status']) && $_POST['status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                    <option value="Inactive" <?php echo (isset($_POST['status']) && $_POST['status'] == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                    <option value="Graduated" <?php echo (isset($_POST['status']) && $_POST['status'] == 'Graduated') ? 'selected' : ''; ?>>Graduated</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="address">Address</label>
            <textarea class="form-control" id="address" name="address"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
        </div>

        <!-- Academic Information -->
        <div class="section-divider">
            <h3><i class="fas fa-graduation-cap"></i> Academic Information</h3>
        </div>

        <div class="form-row-3">
            <div class="form-group">
                <label for="year_level_id">Year Level</label>
                <select class="form-control" id="year_level_id" name="year_level_id">
                    <option value="">-- Select Year Level --</option>
                    <?php 
                    mysqli_data_seek($year_levels_result, 0);
                    while ($year_level = mysqli_fetch_assoc($year_levels_result)): 
                    ?>
                        <option value="<?php echo $year_level['id']; ?>" 
                                <?php echo (isset($_POST['year_level_id']) && $_POST['year_level_id'] == $year_level['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($year_level['year_level_code'] . ' - ' . $year_level['year_level_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="section">Section</label>
                <input type="text" class="form-control" id="section" name="section" 
                       value="<?php echo isset($_POST['section']) ? htmlspecialchars($_POST['section']) : ''; ?>" 
                       placeholder="e.g., Section A">
            </div>
            <div class="form-group">
                <label for="tutor_subject">Subject for Tutor</label>
                <input type="text" class="form-control" id="tutor_subject" name="tutor_subject" 
                       value="<?php echo isset($_POST['tutor_subject']) ? htmlspecialchars($_POST['tutor_subject']) : ''; ?>" 
                       placeholder="e.g., Mathematics">
            </div>
        </div>

        <div class="form-group">
            <label for="assigned_tutor_id">Assign Tutor (Optional)</label>
            <select class="form-control" id="assigned_tutor_id" name="assigned_tutor_id">
                <option value="">-- No Tutor Assigned --</option>
                <?php while ($tutor = mysqli_fetch_assoc($tutors_result)): ?>
                    <option value="<?php echo $tutor['id']; ?>" 
                            <?php echo (isset($_POST['assigned_tutor_id']) && $_POST['assigned_tutor_id'] == $tutor['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($tutor['tutor_id'] . ' - ' . $tutor['first_name'] . ' ' . $tutor['last_name'] . ' (' . $tutor['specialization'] . ')'); ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <small style="color: #666; display: block; margin-top: 5px;">
                <i class="fas fa-info-circle"></i> Select a tutor and specify the subject above to create a tutor-student matching
            </small>
        </div>

        <!-- ID Image Upload -->
        <div class="section-divider">
            <h3><i class="fas fa-id-card"></i> Student ID Image</h3>
        </div>

        <div class="form-group">
            <label for="student_id_image">Upload Student ID Image (Optional)</label>
            <div class="upload-area" id="uploadArea">
                <div class="upload-icon">
                    <i class="fas fa-cloud-upload-alt"></i>
                </div>
                <p style="margin: 10px 0; color: #666;">
                    <strong>Drag and drop an image here</strong><br>
                    or click to browse
                </p>
                <p style="font-size: 12px; color: #999;">
                    Supported formats: JPG, PNG, GIF (Max 5MB)
                </p>
                <input type="file" id="student_id_image" name="student_id_image" 
                       accept="image/jpeg,image/jpg,image/png,image/gif" style="display: none;">
            </div>
            <div id="imagePreview" style="display: none; text-align: center;">
                <img id="previewImg" class="image-preview" src="" alt="Preview">
                <br>
                <button type="button" class="btn btn-secondary" onclick="clearImage()" style="margin-top: 10px;">
                    <i class="fas fa-times"></i> Remove Image
                </button>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" name="add_student" class="btn btn-primary">
                <i class="fas fa-save"></i> Add Student
            </button>
        </div>
    </form>
</div>

<script>
// Image upload handling
const uploadArea = document.getElementById('uploadArea');
const fileInput = document.getElementById('student_id_image');
const imagePreview = document.getElementById('imagePreview');
const previewImg = document.getElementById('previewImg');

uploadArea.addEventListener('click', () => fileInput.click());

fileInput.addEventListener('change', handleFileSelect);

uploadArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadArea.classList.add('dragover');
});

uploadArea.addEventListener('dragleave', () => {
    uploadArea.classList.remove('dragover');
});

uploadArea.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadArea.classList.remove('dragover');
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        fileInput.files = files;
        handleFileSelect();
    }
});

function handleFileSelect() {
    const file = fileInput.files[0];
    if (file) {
        if (file.size > 5000000) {
            alert('File size exceeds 5MB limit');
            fileInput.value = '';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = (e) => {
            previewImg.src = e.target.result;
            uploadArea.style.display = 'none';
            imagePreview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
}

function clearImage() {
    fileInput.value = '';
    uploadArea.style.display = 'block';
    imagePreview.style.display = 'none';
    previewImg.src = '';
}
</script>
