<?php
$success_message = '';
$error_message = '';
$generated_tutor_id = '';

// Create uploads directory if it doesn't exist
$upload_dir = '../uploads/tutor_profiles/';
if (!file_exists($upload_dir)) {
    if (!mkdir($upload_dir, 0755, true)) {
        $error_message = 'Failed to create upload directory. Please check permissions.';
    }
}

// Get the next tutor ID that will be generated
$year_prefix = date('Y');
$next_id_sql = "SELECT COALESCE(MAX(CAST(SUBSTRING(tutor_id, 6) AS UNSIGNED)), 0) + 1 as next_num 
                FROM tutors 
                WHERE tutor_id LIKE CONCAT(?, '-%')";
$next_id_stmt = mysqli_prepare($conn, $next_id_sql);
mysqli_stmt_bind_param($next_id_stmt, "s", $year_prefix);
mysqli_stmt_execute($next_id_stmt);
$next_id_result = mysqli_stmt_get_result($next_id_stmt);
$next_id_row = mysqli_fetch_assoc($next_id_result);
$next_tutor_id = $year_prefix . '-' . str_pad($next_id_row['next_num'], 2, '0', STR_PAD_LEFT);
mysqli_stmt_close($next_id_stmt);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_tutor'])) {
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

    // Handle profile picture upload
    $profile_picture = NULL;
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $file_type = $_FILES['profile_picture']['type'];
        $file_size = $_FILES['profile_picture']['size'];
        $tmp_name = $_FILES['profile_picture']['tmp_name'];
        
        // Validate actual image content
        $image_info = getimagesize($tmp_name);
        if ($image_info === false) {
            $error_message = 'Uploaded file is not a valid image.';
        } elseif (in_array($file_type, $allowed_types) && $file_size <= 5000000) { // 5MB max
            $file_extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            $new_filename = 'tutor_' . uniqid() . '.' . strtolower($file_extension);
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($tmp_name, $upload_path)) {
                $profile_picture = 'uploads/tutor_profiles/' . $new_filename;
            } else {
                $error_message = 'Error uploading image file. Please check permissions.';
            }
        } else {
            $error_message = 'Invalid file type or size. Please upload JPG, PNG, or GIF under 5MB.';
        }
    }

    if (empty($error_message)) {
        // Validate required fields
        if (empty($first_name) || empty($last_name) || empty($email) || empty($hire_date)) {
            $error_message = 'Please fill in all required fields.';
        } else {
            // Check if email already exists
            $check_sql = "SELECT id FROM tutors WHERE email = ?";
            $stmt = mysqli_prepare($conn, $check_sql);
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $check_result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($check_result) > 0) {
                $error_message = 'Email already exists. Please use a different one.';
                mysqli_stmt_close($stmt);
            } else {
                mysqli_stmt_close($stmt);
                
                // Insert new tutor
                $insert_sql = "INSERT INTO tutors (first_name, last_name, email, phone, specialization, qualification, experience_years, hourly_rate, address, profile_picture, status, hire_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $insert_sql);
                mysqli_stmt_bind_param($stmt, "ssssssidssss", $first_name, $last_name, $email, $phone, $specialization, $qualification, $experience_years, $hourly_rate, $address, $profile_picture, $status, $hire_date);
                
                if (mysqli_stmt_execute($stmt)) {
                    // Get the generated tutor_id
                    $last_id = mysqli_insert_id($conn);
                    $get_id_sql = "SELECT tutor_id FROM tutors WHERE id = ?";
                    $id_stmt = mysqli_prepare($conn, $get_id_sql);
                    mysqli_stmt_bind_param($id_stmt, "i", $last_id);
                    mysqli_stmt_execute($id_stmt);
                    $id_result = mysqli_stmt_get_result($id_stmt);
                    $id_row = mysqli_fetch_assoc($id_result);
                    $generated_tutor_id = $id_row['tutor_id'];
                    mysqli_stmt_close($id_stmt);
                    
                    $success_message = 'Tutor added successfully!';
                    $_POST = array();
                    
                    // Recalculate next ID
                    $next_id_stmt = mysqli_prepare($conn, $next_id_sql);
                    mysqli_stmt_bind_param($next_id_stmt, "s", $year_prefix);
                    mysqli_stmt_execute($next_id_stmt);
                    $next_id_result = mysqli_stmt_get_result($next_id_stmt);
                    $next_id_row = mysqli_fetch_assoc($next_id_result);
                    $next_tutor_id = $year_prefix . '-' . str_pad($next_id_row['next_num'], 2, '0', STR_PAD_LEFT);
                    mysqli_stmt_close($next_id_stmt);
                } else {
                    $error_message = 'Error adding tutor: ' . mysqli_error($conn);
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
        border-radius: 50%;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        object-fit: cover;
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
        .form-row {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="card">
    <div class="card-header">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2 class="card-title"><i class="fas fa-user-plus"></i> Add New Tutor</h2>
            <a href="../tutor_registration.php" target="_blank" class="btn" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 10px 20px; font-size: 13px;">
                <i class="fas fa-external-link-alt"></i> Public Registration Form
            </a>
        </div>
        <p style="margin: 10px 0 0 0; color: #666; font-size: 14px;">
            <i class="fas fa-info-circle"></i> Tutors/Teachers can also self-register using the public registration form
        </p>
    </div>

    <?php if ($success_message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            <?php if ($generated_tutor_id): ?>
                <br><strong>Generated Tutor ID: <span style="font-size:1.2em;color:#155724;"><?php echo htmlspecialchars($generated_tutor_id); ?></span></strong>
            <?php endif; ?>
            <br><a href="?page=manage_tutors">View all tutors</a>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="" enctype="multipart/form-data">
        <div class="alert" style="background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb;">
            <i class="fas fa-info-circle"></i> <strong>Auto-Generated ID:</strong> The next tutor will receive ID: <strong style="font-size:1.1em;"><?php echo htmlspecialchars($next_tutor_id); ?></strong>
        </div>

        <!-- Profile Picture Upload -->
        <div class="section-divider">
            <h3><i class="fas fa-camera"></i> Profile Picture</h3>
        </div>

        <div class="form-group">
            <label for="profile_picture">Upload Profile Picture (Optional)</label>
            <div class="upload-area" id="uploadArea">
                <div class="upload-icon">
                    <i class="fas fa-user-circle"></i>
                </div>
                <p style="margin: 10px 0; color: #666;">
                    <strong>Drag and drop a photo here</strong><br>
                    or click to browse
                </p>
                <p style="font-size: 12px; color: #999;">
                    Supported formats: JPG, PNG, GIF (Max 5MB)
                </p>
                <input type="file" id="profile_picture" name="profile_picture" 
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

        <!-- Basic Information -->
        <div class="section-divider">
            <h3><i class="fas fa-user"></i> Basic Information</h3>
        </div>

        <div class="form-group">
            <label for="hire_date">Hire Date <span class="required">*</span></label>
            <input type="date" class="form-control" id="hire_date" name="hire_date" 
                   value="<?php echo isset($_POST['hire_date']) ? htmlspecialchars($_POST['hire_date']) : ''; ?>" 
                   required>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="first_name">First Name <span class="required">*</span></label>
                <input type="text" class="form-control" id="first_name" name="first_name" 
                       value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>" 
                       required>
            </div>
            <div class="form-group">
                <label for="last_name">Last Name <span class="required">*</span></label>
                <input type="text" class="form-control" id="last_name" name="last_name" 
                       value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>" 
                       required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="email">Email <span class="required">*</span></label>
                <input type="email" class="form-control" id="email" name="email" 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                       required>
            </div>
            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="tel" class="form-control" id="phone" name="phone" 
                       value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
            </div>
        </div>

        <!-- Professional Information -->
        <div class="section-divider">
            <h3><i class="fas fa-graduation-cap"></i> Professional Information</h3>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="specialization">Specialization</label>
                <input type="text" class="form-control" id="specialization" name="specialization" 
                       value="<?php echo isset($_POST['specialization']) ? htmlspecialchars($_POST['specialization']) : ''; ?>" 
                       placeholder="e.g., Mathematics, Physics, English">
            </div>
            <div class="form-group">
                <label for="qualification">Qualification</label>
                <input type="text" class="form-control" id="qualification" name="qualification" 
                       value="<?php echo isset($_POST['qualification']) ? htmlspecialchars($_POST['qualification']) : ''; ?>" 
                       placeholder="e.g., PhD, Masters, Bachelor's">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="experience_years">Experience (Years)</label>
                <input type="number" class="form-control" id="experience_years" name="experience_years" 
                       value="<?php echo isset($_POST['experience_years']) ? htmlspecialchars($_POST['experience_years']) : '0'; ?>" 
                       min="0" max="50">
            </div>
            <div class="form-group">
                <label for="hourly_rate">Hourly Rate ($)</label>
                <input type="number" class="form-control" id="hourly_rate" name="hourly_rate" 
                       value="<?php echo isset($_POST['hourly_rate']) ? htmlspecialchars($_POST['hourly_rate']) : '0'; ?>" 
                       min="0" step="0.01">
            </div>
        </div>

        <div class="form-group">
            <label for="status">Status</label>
            <select class="form-control" id="status" name="status">
                <option value="Active" <?php echo (isset($_POST['status']) && $_POST['status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                <option value="Inactive" <?php echo (isset($_POST['status']) && $_POST['status'] == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                <option value="On Leave" <?php echo (isset($_POST['status']) && $_POST['status'] == 'On Leave') ? 'selected' : ''; ?>>On Leave</option>
            </select>
        </div>

        <div class="form-group">
            <label for="address">Address</label>
            <textarea class="form-control" id="address" name="address"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" name="add_tutor" class="btn btn-primary">
                <i class="fas fa-save"></i> Add Tutor
            </button>
            <a href="?page=manage_tutors" class="btn btn-secondary">
                <i class="fas fa-times"></i> Cancel
            </a>
        </div>
    </form>
</div>

<script>
// Image upload handling
const uploadArea = document.getElementById('uploadArea');
const fileInput = document.getElementById('profile_picture');
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
