<?php
if (!isset($conn)) { include "../db_conn.php"; }
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_staff'])) {
	$username = trim($_POST['username'] ?? '');
	$name = trim($_POST['name'] ?? '');
	$email = trim($_POST['email'] ?? '');
	$password = $_POST['password'] ?? '';
	$confirm_password = $_POST['confirm_password'] ?? '';

	if ($username === '' || $name === '' || $password === '' || $confirm_password === '') {
		$error_message = 'Please fill in all required fields.';
	} elseif ($password !== $confirm_password) {
		$error_message = 'Passwords do not match.';
	} else {
		// Check if username already exists
		$check_sql = "SELECT id FROM users WHERE username = ? LIMIT 1";
		$check_stmt = mysqli_prepare($conn, $check_sql);
		mysqli_stmt_bind_param($check_stmt, "s", $username);
		mysqli_stmt_execute($check_stmt);
		$check_result = mysqli_stmt_get_result($check_stmt);
		if ($check_result && mysqli_num_rows($check_result) > 0) {
			$error_message = 'Username already exists. Please choose another.';
		} else {
			// Insert new staff user with role 'user'
			$hashed = password_hash($password, PASSWORD_DEFAULT);
			$insert_sql = "INSERT INTO users (role, username, password, name, email, status) VALUES ('user', ?, ?, ?, ?, 'Active')";
			$insert_stmt = mysqli_prepare($conn, $insert_sql);
			mysqli_stmt_bind_param($insert_stmt, "ssss", $username, $hashed, $name, $email);
			if (mysqli_stmt_execute($insert_stmt)) {
				$success_message = 'Staff account created successfully. Username: ' . htmlspecialchars($username);
				$_POST = array();
			} else {
				$error_message = 'Error creating staff: ' . mysqli_error($conn);
			}
			mysqli_stmt_close($insert_stmt);
		}
		mysqli_stmt_close($check_stmt);
	}
}
?>

<div style="background:#fff;padding:30px;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);max-width:720px;">
	<h2><i class="fas fa-user-tie"></i> Add Staff Account</h2>
	<p>Create a login for staff. Accounts are stored in <code>users</code> with role <strong>user</strong>.</p>

	<?php if (!empty($success_message)) { ?>
		<div class="alert" style="background:#d4edda;border:1px solid #c3e6cb;color:#155724;padding:12px 20px;border-radius:4px;margin:15px 0;">
			<?php echo $success_message; ?>
		</div>
	<?php } ?>
	<?php if (!empty($error_message)) { ?>
		<div class="alert" style="background:#f8d7da;border:1px solid #f5c6cb;color:#721c24;padding:12px 20px;border-radius:4px;margin:15px 0;">
			<?php echo $error_message; ?>
		</div>
	<?php } ?>

	<form method="post" action="">
		<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
			<div>
				<label style="display:block;margin-bottom:6px;font-weight:600;color:#2c3e50;">Username *</label>
				<input type="text" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required style="width:100%;padding:10px;border:1px solid #ddd;border-radius:4px;">
			</div>
			<div>
				<label style="display:block;margin-bottom:6px;font-weight:600;color:#2c3e50;">Full Name *</label>
				<input type="text" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required style="width:100%;padding:10px;border:1px solid #ddd;border-radius:4px;">
			</div>
			<div>
				<label style="display:block;margin-bottom:6px;font-weight:600;color:#2c3e50;">Email</label>
				<input type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:4px;">
			</div>
			<div></div>
			<div>
				<label style="display:block;margin-bottom:6px;font-weight:600;color:#2c3e50;">Password *</label>
				<input type="password" name="password" required style="width:100%;padding:10px;border:1px solid #ddd;border-radius:4px;">
			</div>
			<div>
				<label style="display:block;margin-bottom:6px;font-weight:600;color:#2c3e50;">Confirm Password *</label>
				<input type="password" name="confirm_password" required style="width:100%;padding:10px;border:1px solid #ddd;border-radius:4px;">
			</div>
		</div>
		<div style="margin-top:18px;">
			<button type="submit" name="add_staff" class="btn" style="background:#3498db;color:#fff;padding:12px 22px;border:none;border-radius:4px;cursor:pointer;">
				<i class="fas fa-save"></i> Create Staff
			</button>
		</div>
	</form>
</div>