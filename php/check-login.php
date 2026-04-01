<?php  
session_start();
include "../db_conn.php";

if (isset($_POST['username']) && isset($_POST['password'])) {

	function test_input($data) {
	  $data = trim($data);
	  $data = stripslashes($data);
	  $data = htmlspecialchars($data);
	  return $data;
	}

	$username = test_input($_POST['username']);
	$password = test_input($_POST['password']);

	if (empty($username)) {
		header("Location: ../index.php?error=User Name is Required");
		exit();
	}else if (empty($password)) {
		header("Location: ../index.php?error=Password is Required");
		exit();
	}else {

		// Use prepared statements to prevent SQL injection
		$sql = "SELECT * FROM users WHERE username=? LIMIT 1";
		$stmt = mysqli_prepare($conn, $sql);
		mysqli_stmt_bind_param($stmt, "s", $username);
		mysqli_stmt_execute($stmt);
		$result = mysqli_stmt_get_result($stmt);

		if ($result && mysqli_num_rows($result) === 1) {
			$row = mysqli_fetch_assoc($result);
			$stored_hash = $row['password'];

			$valid_password = false;

			// Support modern password_hash hashed passwords
			if (password_verify($password, $stored_hash)) {
				$valid_password = true;
			}
			// Legacy support for old MD5 hashed passwords
			elseif ($stored_hash === md5($password)) {
				$valid_password = true;
				// Upgrade legacy hash
				$new_hash = password_hash($password, PASSWORD_DEFAULT);
				$update_sql = "UPDATE users SET password=? WHERE id=?";
				$update_stmt = mysqli_prepare($conn, $update_sql);
				if ($update_stmt) {
					mysqli_stmt_bind_param($update_stmt, "si", $new_hash, $row['id']);
					mysqli_stmt_execute($update_stmt);
					mysqli_stmt_close($update_stmt);
				}
			}

			if ($valid_password) {
				$_SESSION['name'] = $row['name'];
				$_SESSION['id'] = $row['id'];
				$_SESSION['role'] = $row['role'];
				$_SESSION['username'] = $row['username'];

				header("Location: ../home.php");
				exit();
			}
		}

		header("Location: ../index.php?error=Incorrect User name or password");
		exit();
	}
	
}else {
	header("Location: ../index.php");
}