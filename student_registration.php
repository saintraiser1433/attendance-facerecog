<?php 
session_start();
include "db_conn.php";

$success_message = '';
$error_message = '';
$generated_student_id = '';

// Create uploads directory if it doesn't exist
$upload_dir = 'uploads/student_ids/';
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
$tutors_sql = "SELECT id, tutor_id, CONCAT(first_name, ' ', last_name) as full_name, specialization, experience_years, hourly_rate FROM tutors WHERE status = 'Active' ORDER BY first_name ASC";
$tutors_result = mysqli_query($conn, $tutors_sql);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register_student'])) {
    $first_name = mysqli_real_escape_string($conn, trim($_POST['first_name']));
    $last_name = mysqli_real_escape_string($conn, trim($_POST['last_name']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $phone = isset($_POST['phone']) ? mysqli_real_escape_string($conn, trim($_POST['phone'])) : '';
    $date_of_birth = isset($_POST['date_of_birth']) ? mysqli_real_escape_string($conn, $_POST['date_of_birth']) : '';
    $address = isset($_POST['address']) ? mysqli_real_escape_string($conn, trim($_POST['address'])) : '';
    $year_level_id = !empty($_POST['year_level_id']) ? intval($_POST['year_level_id']) : NULL;
    $section = isset($_POST['section']) ? mysqli_real_escape_string($conn, trim($_POST['section'])) : '';
    $enrollment_date = date('Y-m-d'); // Auto-set to today
    $status = 'Active'; // Default status
    $assigned_tutor_id = !empty($_POST['assigned_tutor_id']) ? intval($_POST['assigned_tutor_id']) : NULL;
    $tutor_subject = isset($_POST['tutor_subject']) ? mysqli_real_escape_string($conn, trim($_POST['tutor_subject'])) : '';
    
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

    if (empty($error_message)) {
        if (empty($first_name) || empty($last_name) || empty($email)) {
            $error_message = 'Please fill in all required fields (First Name, Last Name, Email).';
        } else {
            // Validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error_message = 'Please enter a valid email address.';
            } else {
                // Check if email already exists
                $check_sql = "SELECT id FROM students WHERE email = ?";
                $stmt = mysqli_prepare($conn, $check_sql);
                mysqli_stmt_bind_param($stmt, "s", $email);
                mysqli_stmt_execute($stmt);
                $check_result = mysqli_stmt_get_result($stmt);
                
                if (mysqli_num_rows($check_result) > 0) {
                    $error_message = 'This email is already registered. Please use a different email or contact admin.';
                    mysqli_stmt_close($stmt);
                } else {
                    mysqli_stmt_close($stmt);
                    
                    // Insert new student
                    $insert_sql = "INSERT INTO students (first_name, last_name, email, phone, date_of_birth, address, year_level_id, section, student_id_image, enrollment_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = mysqli_prepare($conn, $insert_sql);
                    mysqli_stmt_bind_param($stmt, "ssssssissss", $first_name, $last_name, $email, $phone, $date_of_birth, $address, $year_level_id, $section, $student_id_image, $enrollment_date, $status);
                    
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
                        
                        // Create user account for the student
                        // Username: based on first name (e.g., john)
                        $username = strtolower($first_name);
                        $username = preg_replace('/[^a-z0-9]/', '', $username); // Remove special characters
                        
                        // Password: lastname + first letter of firstname (e.g., doej)
                        $first_letter = strtolower(substr($first_name, 0, 1));
                        $random_password = strtolower($last_name) . $first_letter;
                        $random_password = preg_replace('/[^a-z0-9]/', '', $random_password); // Remove special characters
                        $hashed_password = password_hash($random_password, PASSWORD_DEFAULT);
                        
                        // Check if username already exists, if so add a number
                        $check_username_sql = "SELECT id FROM users WHERE username = ?";
                        $check_stmt = mysqli_prepare($conn, $check_username_sql);
                        mysqli_stmt_bind_param($check_stmt, "s", $username);
                        mysqli_stmt_execute($check_stmt);
                        $check_result = mysqli_stmt_get_result($check_stmt);
                        
                        if (mysqli_num_rows($check_result) > 0) {
                            $username = $username . rand(100, 999);
                        }
                        mysqli_stmt_close($check_stmt);
                        
                        // Insert user account with correct role
                        $user_sql = "INSERT INTO users (username, password, role, name, student_id) VALUES (?, ?, 'student', ?, ?)";
                        $user_stmt = mysqli_prepare($conn, $user_sql);
                        $full_name = $first_name . ' ' . $last_name;
                        mysqli_stmt_bind_param($user_stmt, "ssss", $username, $hashed_password, $full_name, $generated_student_id);
                        mysqli_stmt_execute($user_stmt);
                        mysqli_stmt_close($user_stmt);
                        
                        // Assign tutor if selected
                        if ($assigned_tutor_id && !empty($tutor_subject)) {
                            $match_sql = "INSERT INTO tutor_student_matching (tutor_id, student_id, subject, status, start_date) VALUES (?, ?, ?, 'Active', CURDATE())";
                            $match_stmt = mysqli_prepare($conn, $match_sql);
                            mysqli_stmt_bind_param($match_stmt, "iis", $assigned_tutor_id, $last_student_id, $tutor_subject);
                            mysqli_stmt_execute($match_stmt);
                            mysqli_stmt_close($match_stmt);
                        }
                        
                        $success_message = 'Registration successful! Your Student ID is: <strong>' . htmlspecialchars($generated_student_id) . '</strong><br>Your login credentials are:<br>Username: <strong>' . htmlspecialchars($username) . '</strong><br>Password: <strong>' . htmlspecialchars($random_password) . '</strong><br>Please save this information for future reference.';
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
                        $error_message = 'Error during registration: ' . mysqli_error($conn);
                    }
                    mysqli_stmt_close($stmt);
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Student Registration - Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        .registration-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .card {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
            margin-bottom: 30px;
        }
        .card-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #667eea;
        }
        .card-header h1 {
            color: #667eea;
            font-weight: bold;
            margin: 0;
        }
        .card-header p {
            color: #666;
            margin-top: 10px;
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
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .btn {
            padding: 14px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            width: 100%;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        .btn-secondary {
            background: #95a5a6;
            color: #fff;
        }
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        .upload-area {
            border: 2px dashed #ddd;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            background: #f8f9fa;
            transition: all 0.3s;
            cursor: pointer;
        }
        .upload-area:hover {
            border-color: #667eea;
            background: #e3f2fd;
        }
        .upload-area.dragover {
            border-color: #764ba2;
            background: #d1c4e9;
        }
        .upload-icon {
            font-size: 48px;
            color: #667eea;
            margin-bottom: 15px;
        }
        .image-preview {
            max-width: 200px;
            max-height: 200px;
            margin: 15px auto;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .alert {
            padding: 15px 20px;
            margin-bottom: 25px;
            border-radius: 8px;
            font-weight: 500;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 2px solid #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 2px solid #f5c6cb;
        }
        .section-divider {
            margin: 30px 0 20px 0;
            padding: 12px 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px;
        }
        .section-divider h3 {
            margin: 0;
            font-size: 1.1em;
        }
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        .back-link a {
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            padding: 10px 20px;
            background: rgba(255,255,255,0.2);
            border-radius: 8px;
            transition: all 0.3s;
        }
        .back-link a:hover {
            background: rgba(255,255,255,0.3);
        }
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            .card {
                padding: 25px;
            }
        }
    </style>
</head>
<body>
    <div class="registration-container">
        <div class="card">
            <div class="card-header">
                <h1><i class="fas fa-user-graduate"></i> Student Registration</h1>
                <p>Register as a new student in the system</p>
            </div>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                </div>
                <div class="card" style="margin-bottom:20px;">
                    <div class="section-divider"><h3><i class="fas fa-fingerprint"></i> Enroll Fingerprint (Recommended)</h3></div>
                    <p>Click the button below to scan and enroll the student's fingerprint. You can also enroll later from admin.</p>
                    <div id="enroll-status" style="background:#fff3cd;padding:12px;border-radius:4px;margin:15px 0;color:#856404;border:1px solid #ffeeba;">Scanner ready. Click to enroll.</div>
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
                        <span id="fp-indicator" style="display:inline-block;min-width:120px;text-align:center;padding:6px 10px;border-radius:14px;background:#ffeeba;color:#856404;border:1px solid #ffe8a1;">No Scan</span>
                        <small style="color:#6c757d;">Indicator: Valid (green), Not Match (red), New (blue)</small>
                    </div>
                    <button id="btn-enroll-student" class="btn btn-primary"><i class="fas fa-fingerprint"></i> Scan & Enroll Now</button>
                    <div id="enroll-result" style="margin-top:12px;"></div>
                </div>
                <a href="index.php" class="btn btn-primary" style="width: auto;">
                    <i class="fas fa-sign-in-alt"></i> Go to Login
                </a>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <?php if (!$success_message): ?>
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="alert" style="background-color: #d1ecf1; color: #0c5460; border: 2px solid #bee5eb; margin-bottom: 25px;">
                    <i class="fas fa-info-circle"></i> <strong>Auto-Generated ID:</strong> The next student will receive ID: <strong style="font-size:1.2em;"><?php echo htmlspecialchars($next_student_id); ?></strong>
                </div>

                <!-- Basic Information -->
                <div class="section-divider">
                    <h3><i class="fas fa-user"></i> Basic Information</h3>
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
                        <label for="email">Email <span class="required">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" class="form-control" id="phone" name="phone" 
                               value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="date_of_birth">Date of Birth</label>
                    <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" 
                           value="<?php echo isset($_POST['date_of_birth']) ? htmlspecialchars($_POST['date_of_birth']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea class="form-control" id="address" name="address"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                </div>

                <!-- Academic Information -->
                <div class="section-divider">
                    <h3><i class="fas fa-graduation-cap"></i> Academic Information</h3>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="year_level_id">Year Level</label>
                        <select class="form-control" id="year_level_id" name="year_level_id">
                            <option value="">-- Select Year Level --</option>
                            <?php 
                            if ($year_levels_result) {
                                mysqli_data_seek($year_levels_result, 0);
                                while ($year_level = mysqli_fetch_assoc($year_levels_result)): 
                            ?>
                                <option value="<?php echo $year_level['id']; ?>" 
                                        <?php echo (isset($_POST['year_level_id']) && $_POST['year_level_id'] == $year_level['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($year_level['year_level_code'] . ' - ' . $year_level['year_level_name']); ?>
                                </option>
                            <?php 
                                endwhile;
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="section">Section</label>
                        <input type="text" class="form-control" id="section" name="section" 
                               value="<?php echo isset($_POST['section']) ? htmlspecialchars($_POST['section']) : ''; ?>" 
                               placeholder="e.g., Section A">
                    </div>
                </div>

                <div class="form-group">
                    <label for="assigned_tutor_id">Choose a Tutor (Optional)</label>
                    <select class="form-control" id="assigned_tutor_id" name="assigned_tutor_id" onchange="fillTutorSubject()">
                        <option value="" data-specialization="">-- No Tutor Selected --</option>
                        <?php 
                        if ($tutors_result) {
                            mysqli_data_seek($tutors_result, 0);
                            while ($tutor = mysqli_fetch_assoc($tutors_result)): 
                        ?>
                            <option value="<?php echo $tutor['id']; ?>" 
                                    data-specialization="<?php echo htmlspecialchars($tutor['specialization']); ?>"
                                    <?php echo (isset($_POST['assigned_tutor_id']) && $_POST['assigned_tutor_id'] == $tutor['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($tutor['tutor_id'] . ' - ' . $tutor['full_name'] . ' (' . $tutor['specialization'] . ')'); ?>
                            </option>
                        <?php 
                            endwhile;
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="tutor_subject">Subject for Tutor (Optional)</label>
                    <input type="text" class="form-control" id="tutor_subject" name="tutor_subject" 
                           value="<?php echo isset($_POST['tutor_subject']) ? htmlspecialchars($_POST['tutor_subject']) : ''; ?>" 
                           placeholder="e.g., Mathematics, Science, English">
                    <small style="color: #666; display: block; margin-top: 8px;">
                        <i class="fas fa-info-circle"></i> Specify the subject if you selected a tutor above
                    </small>
                </div>

                <!-- ID Image Upload -->
                <div class="section-divider">
                    <h3><i class="fas fa-id-card"></i> Student ID Image (Optional)</h3>
                </div>

                <div class="form-group">
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

                <div style="margin-top: 30px;">
                    <button type="submit" name="register_student" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Register Now
                    </button>
                </div>
            </form>
            <?php endif; ?>
        </div>

        <!-- Tutor List Section -->
        <div class="card" style="margin-top: 30px;">
            <div class="card-header">
                <h1><i class="fas fa-chalkboard-teacher"></i> Available Tutors</h1>
                <p>Browse our qualified tutors and their specializations</p>
            </div>

            <?php 
            // Reset the result pointer to display tutors again
            if ($tutors_result && mysqli_num_rows($tutors_result) > 0) {
                mysqli_data_seek($tutors_result, 0);
            }
            ?>
            
            <?php if ($tutors_result && mysqli_num_rows($tutors_result) > 0): ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                    <?php while ($tutor = mysqli_fetch_assoc($tutors_result)): ?>
                        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; padding: 25px; color: white; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                            <div style="display: flex; align-items: center; margin-bottom: 15px;">
                                <div style="width: 60px; height: 60px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 15px;">
                                    <i class="fas fa-user-tie" style="font-size: 28px;"></i>
                                </div>
                                <div>
                                    <h3 style="margin: 0; font-size: 1.3em; font-weight: bold;"><?php echo htmlspecialchars($tutor['full_name']); ?></h3>
                                    <p style="margin: 5px 0 0; opacity: 0.9; font-size: 0.9em;">ID: <?php echo htmlspecialchars($tutor['tutor_id']); ?></p>
                                </div>
                            </div>
                            
                            <div style="background: rgba(255,255,255,0.15); border-radius: 8px; padding: 15px; margin-bottom: 12px;">
                                <p style="margin: 0; font-size: 0.85em; opacity: 0.8; text-transform: uppercase; letter-spacing: 1px;">Specialization</p>
                                <p style="margin: 5px 0 0; font-size: 1.1em; font-weight: 600;">
                                    <i class="fas fa-book"></i> <?php echo htmlspecialchars($tutor['specialization'] ?? 'General'); ?>
                                </p>
                            </div>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                <div style="background: rgba(255,255,255,0.15); border-radius: 8px; padding: 12px; text-align: center;">
                                    <p style="margin: 0; font-size: 0.8em; opacity: 0.8;">Experience</p>
                                    <p style="margin: 5px 0 0; font-size: 1.3em; font-weight: bold;">
                                        <?php echo htmlspecialchars($tutor['experience_years']); ?> <span style="font-size: 0.7em;">years</span>
                                    </p>
                                </div>
                                <div style="background: rgba(255,255,255,0.15); border-radius: 8px; padding: 12px; text-align: center;">
                                    <p style="margin: 0; font-size: 0.8em; opacity: 0.8;">Rate/Hour</p>
                                    <p style="margin: 5px 0 0; font-size: 1.3em; font-weight: bold;">
                                        $<?php echo number_format($tutor['hourly_rate'], 2); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #666;">
                    <i class="fas fa-user-slash" style="font-size: 48px; margin-bottom: 15px; opacity: 0.3;"></i>
                    <p style="font-size: 1.1em;">No tutors available at the moment.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="back-link">
            <a href="index.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
        </div>
    </div>

    <script src="js/websdk.client.bundle.min.js"></script>
    <script src="js/fingerprint_handler.js"></script>
    <script>
    // Fallback to CDN if local file is not available
    if (typeof Fingerprint === 'undefined') {
        var script = document.createElement('script');
        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/digitalpersona/1.0.0/websdk.client.bundle.min.js';
        script.onload = function() {
            console.log('Loaded WebSDK from CDN');
        };
        script.onerror = function() {
            console.error('Failed to load WebSDK from CDN');
            // Disable fingerprint functionality if SDK is not available
            var enrollBtn = document.getElementById('btn-enroll-student');
            if (enrollBtn) {
                enrollBtn.disabled = true;
                enrollBtn.textContent = 'Fingerprint Scanner Unavailable';
                enrollBtn.title = 'DigitalPersona WebSDK not found. Please contact administrator.';
            }
        };
        document.head.appendChild(script);
    }
    </script>
    <script>
    (function(){
        // Check if DigitalPersona SDK is available before proceeding
        if (typeof Fingerprint === 'undefined') {
            console.warn('DigitalPersona SDK not loaded. Fingerprint functionality will be disabled.');
            // Optionally disable fingerprint-related UI elements
            var enrollBtn = document.getElementById('btn-enroll-student');
            if (enrollBtn) {
                enrollBtn.disabled = true;
                enrollBtn.textContent = 'Fingerprint Scanner Unavailable';
                enrollBtn.title = 'DigitalPersona WebSDK not found. Please contact administrator.';
            }
            // Show a message to the user
            var statusEl = document.getElementById('enroll-status');
            if (statusEl) {
                statusEl.innerHTML = 'Fingerprint scanner not available. Please contact administrator.';
                statusEl.style.background = '#f8d7da';
                statusEl.style.color = '#721c24';
                statusEl.style.border = '1px solid #f5c6cb';
            }
            return;
        }
        
        var btn = document.getElementById('btn-enroll-student');
        if (btn) {
            var statusEl = document.getElementById('enroll-status');
            var resultEl = document.getElementById('enroll-result');
            var indicator = document.getElementById('fp-indicator');
            btn.addEventListener('click', async function(){
                btn.disabled = true;
                statusEl.textContent = 'Scanning...';
                statusEl.style.background = '#d1ecf1';
                statusEl.style.color = '#0c5460';
                try{
                    // Initialize FingerprintManager and capture sample
                    const fpManager = new FingerprintHandler();
                    const initResult = await fpManager.initialize();
                    if (!initResult.success) {
                        throw new Error(initResult.error);
                    }
                    
                    // Capture fingerprint sample
                    const captureResult = await fpManager.acquireSample();
                    if (!captureResult.success) {
                        throw new Error(captureResult.error);
                    }
                    
                    var captured = captureResult.sample;
                    // Verify against existing templates to show indicator
                    var body = new URLSearchParams();
                    body.append('user_type','student');
                    body.append('template', captured);
                    var vres = await fetch('php/fingerprint/api/verify.php', { method:'POST', headers:{ 'Content-Type':'application/x-www-form-urlencoded' }, body: body.toString() });
                    var vjson = await vres.json();
                    var currentId = <?php echo isset($last_student_id) ? (int)$last_student_id : 0; ?>;
                    if (vjson && vjson.ok && vjson.match) {
                        if (vjson.user_id === currentId) {
                            indicator.textContent = 'Valid Match';
                            indicator.style.background = '#d4edda';
                            indicator.style.color = '#155724';
                            indicator.style.border = '1px solid #c3e6cb';
                        } else {
                            indicator.textContent = 'Not Match';
                            indicator.style.background = '#f8d7da';
                            indicator.style.color = '#721c24';
                            indicator.style.border = '1px solid #f5c6cb';
                        }
                    } else {
                        indicator.textContent = 'New Template';
                        indicator.style.background = '#d1ecf1';
                        indicator.style.color = '#0c5460';
                        indicator.style.border = '1px solid #bee5eb';
                    }
                    // Proceed to enroll captured template for this student
                    var ebody = new URLSearchParams();
                    ebody.append('user_id', String(currentId));
                    ebody.append('user_type', 'student');
                    ebody.append('template', captured);
                    var eresp = await fetch('php/fingerprint/api/enroll.php', { method:'POST', headers:{ 'Content-Type':'application/x-www-form-urlencoded' }, body: ebody.toString() });
                    var ejson = await eresp.json();
                    if (ejson && ejson.ok){
                        resultEl.innerHTML = '<div style="background:#d4edda;border:1px solid #c3e6cb;color:#155724;padding:12px;border-radius:4px;">Fingerprint enrolled successfully.</div>';
                    } else {
                        resultEl.innerHTML = '<div style="background:#f8d7da;border:1px solid #f5c6cb;color:#721c24;padding:12px;border-radius:4px;">Failed to enroll: '+(ejson && ejson.error ? ejson.error : 'Unknown error')+'</div>';
                    }
                }catch(e){
                    resultEl.innerHTML = '<div style="background:#f8d7da;border:1px solid #f5c6cb;color:#721c24;padding:12px;border-radius:4px;">Error: '+(e && e.message ? e.message : 'Enroll failed')+'</div>';
                } finally {
                    btn.disabled = false;
                    statusEl.textContent = 'Scanner ready. Click to enroll.';
                    statusEl.style.background = '#fff3cd';
                    statusEl.style.color = '#856404';
                }
            });
        }
    })();
    // Auto-fill subject based on tutor specialization
    function fillTutorSubject() {
        const tutorSelect = document.getElementById('assigned_tutor_id');
        const subjectInput = document.getElementById('tutor_subject');
        const selectedOption = tutorSelect.options[tutorSelect.selectedIndex];
        const specialization = selectedOption.getAttribute('data-specialization');
        
        if (specialization) {
            subjectInput.value = specialization;
        } else {
            subjectInput.value = '';
        }
    }

    // Image upload handling
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('student_id_image');
    const imagePreview = document.getElementById('imagePreview');
    const previewImg = document.getElementById('previewImg');

    if (uploadArea && fileInput) {
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
    }

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
</body>
</html>
