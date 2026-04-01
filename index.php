<?php 
   // Set timezone to Asia/Manila
   date_default_timezone_set('Asia/Manila');
   
   session_start();
   if (!isset($_SESSION['username']) && !isset($_SESSION['id'])) {   ?>
<!DOCTYPE html>
<html>
<head>
	<title>Attendance & Information System - Login</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-giJF6kkoqNQ00vy+HMDP7azOuL0xtbfIcaT9wjKHr8RbDVddVHyTfAAsrekwKmP1" crossorigin="anonymous">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
	<style>
		body {
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
		}
		.login-container {
			min-height: 100vh;
			display: flex;
			align-items: center;
			justify-content: center;
			padding: 20px;
		}
		.login-form {
			background: white;
			border-radius: 15px;
			box-shadow: 0 10px 40px rgba(0,0,0,0.2);
			width: 100%;
			max-width: 900px;
		}
		.login-header {
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
			color: white;
			padding: 30px;
			border-radius: 15px 15px 0 0;
			text-align: center;
		}
		.login-header h1 {
			margin: 0;
			font-size: 1.8em;
		}
		.login-body {
			padding: 30px;
		}
		.btn-primary {
			width: 100%;
			padding: 12px;
			font-weight: 600;
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
			border: none;
		}
		.btn-primary:hover {
			transform: translateY(-2px);
			box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
		}
		.btn-outline-primary {
			width: 100%;
			padding: 12px;
			font-weight: 600;
			color: #667eea;
			border: 2px solid #667eea;
			margin-top: 10px;
		}
		.btn-outline-primary:hover {
			background: #667eea;
			color: white;
		}
		.form-control {
			padding: 12px;
			border: 2px solid #ddd;
			border-radius: 8px;
		}
		.form-control:focus {
			border-color: #667eea;
			box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
		}
		.divider {
			text-align: center;
			margin: 20px 0;
			position: relative;
		}
		.divider::before {
			content: '';
			position: absolute;
			left: 0;
			top: 50%;
			width: 100%;
			height: 1px;
			background: #ddd;
		}
		.divider span {
			background: white;
			padding: 0 15px;
			position: relative;
			color: #666;
			font-size: 14px;
		}
		.fingerprint-login {
			border-left: 1px solid #eee;
			padding-left: 30px;
		}
		@media (max-width: 768px) {
			.fingerprint-login {
				border-left: none;
				border-top: 1px solid #eee;
				padding-left: 0;
				padding-top: 30px;
				margin-top: 30px;
			}
		}
		.fingerprint-section {
			text-align: center;
			padding: 20px;
			background: #f8f9fa;
			border-radius: 10px;
			margin-bottom: 20px;
		}
		.fingerprint-btn {
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
			color: white;
			border: none;
			padding: 15px 30px;
			border-radius: 50px;
			font-size: 18px;
			cursor: pointer;
			transition: all 0.3s;
			margin: 15px 0;
		}
		.fingerprint-btn:hover {
			transform: scale(1.05);
			box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
		}
		.fingerprint-btn:disabled {
			opacity: 0.7;
			transform: none;
			cursor: not-allowed;
		}
		.status-message {
			padding: 10px;
			border-radius: 5px;
			margin: 10px 0;
			font-weight: 500;
		}
		.status-info {
			background: #d1ecf1;
			color: #0c5460;
			border: 1px solid #bee5eb;
		}
		.status-success {
			background: #d4edda;
			color: #155724;
			border: 1px solid #c3e6cb;
		}
		.status-error {
			background: #f8d7da;
			color: #721c24;
			border: 1px solid #f5c6cb;
		}
	</style>
</head>
<body>
      <div class="login-container">
      	<div class="login-form">
      		<div class="login-header">
      			<i class="fas fa-user-circle" style="font-size: 3em; margin-bottom: 10px;"></i>
      			<h1>LOGIN</h1>
      			<p style="margin: 5px 0 0 0; font-size: 0.9em; opacity: 0.9;">Attendance & Information System</p>
      		</div>
      		<div class="login-body">
				<div class="row">
					<div class="col-md-6">
						<h3 class="text-center mb-4"><i class="fas fa-key"></i> Credential Login</h3>
						<form action="php/check-login.php" method="post">
							<?php if (isset($_GET['error'])) { ?>
							<div class="alert alert-danger" role="alert">
								<i class="fas fa-exclamation-circle"></i>
								<?=$_GET['error']?>
							</div>
							<?php } ?>
							<?php if (isset($_GET['success'])) { ?>
							<div class="alert alert-success" role="alert">
								<i class="fas fa-check-circle"></i>
								<?=$_GET['success']?>
							</div>
							<?php } ?>
							<div class="mb-3">
								<label for="username" class="form-label">
									<i class="fas fa-user"></i> User name
								</label>
								<input type="text" 
									   class="form-control" 
									   name="username" 
									   id="username"
									   placeholder="Enter your username">
							</div>
							<div class="mb-3">
								<label for="password" class="form-label">
									<i class="fas fa-lock"></i> Password
								</label>
								<input type="password" 
									   name="password" 
									   class="form-control" 
									   id="password"
									   placeholder="Enter your password">
							</div>
						 
							<button type="submit" class="btn btn-primary">
								<i class="fas fa-sign-in-alt"></i> LOGIN
							</button>
						</form>

						<div class="divider">
							<span>New User?</span>
						</div>

						<a href="student_registration.php" class="btn btn-outline-primary">
							<i class="fas fa-user-graduate"></i> Register as Student
						</a>

						<a href="tutor_registration.php" class="btn btn-outline-primary" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; border: none;">
							<i class="fas fa-chalkboard-teacher"></i> Register as Tutor/Teacher
						</a>
					</div>
					<div class="col-md-6 fingerprint-login">
						<h3 class="text-center mb-4"><i class="fas fa-fingerprint"></i> Fingerprint Login</h3>
						<p class="text-center text-muted mb-4">Scan your fingerprint to mark attendance</p>
						
						<div class="fingerprint-section">
							<!-- Reader Selection -->
							<div class="mb-3">
								<label for="verifyReaderSelect" class="form-label">
									<i class="fas fa-scanner"></i> Select Fingerprint Reader
								</label>
								<select id="verifyReaderSelect" class="form-control">
									<option>Select Fingerprint Reader</option>
								</select>
							</div>
							
							<div id="fp-status" class="status-message status-info">
								Please select a fingerprint reader and click scan.
							</div>
							
							<!-- Fingerprint verification icons -->
							<div id="verificationFingers" style="text-align: center; margin: 20px 0;">
								<div id="verification1" style="display: inline-block; margin: 0 10px;">
									<span class="verifyicon icon-indexfinger-not-enrolled" title="not_enrolled"></span>
								</div>
								<div id="verification2" style="display: inline-block; margin: 0 10px;">
									<span class="verifyicon icon-indexfinger-not-enrolled" title="not_enrolled"></span>
								</div>
							</div>
							
							<div style="display: flex; gap: 10px; justify-content: center;">
								<button id="btn-scan-in" class="fingerprint-btn in" style="flex: 1;">
									<i class="fas fa-sign-in-alt"></i> TIME IN
								</button>
								<button id="btn-scan-out" class="fingerprint-btn out" style="flex: 1; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
									<i class="fas fa-sign-out-alt"></i> TIME OUT
								</button>
							</div>
							
							<input type="hidden" id="types" value="IN">
							<input type="hidden" id="sched" value="1">
							
							<div id="fp-result" class="mt-3">
								<!-- Attendance result will be displayed here -->
								<div id="attendance-card" style="display:none; padding: 15px; border-radius: 8px; background: #f8f9fa; border: 1px solid #dee2e6;">
									<div class="row align-items-center">
										<div class="col-3">
											<img id="studimg" src="" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover;" alt="Profile">
										</div>
										<div class="col-9">
											<h6 class="mb-1"><strong class="stud"></strong></h6>
											<p class="mb-0 name" style="font-size: 14px;"></p>
											<p class="mb-0 yearlvl" style="font-size: 12px; color: #6c757d;"></p>
											<p class="mb-0 coursedtl" style="font-size: 12px; color: #6c757d;"></p>
											<p class="mb-0 res mt-2" style="font-size: 13px; font-weight: 600;"></p>
										</div>
									</div>
								</div>
							</div>
							
							<div class="mt-4">
								<small class="text-muted">
									<i class="fas fa-info-circle"></i> This will automatically mark your attendance if a match is found
								</small>
							</div>
						</div>
						
						<div class="alert alert-info">
							<i class="fas fa-info-circle"></i> 
							<strong>Note:</strong> Make sure your fingerprint is properly enrolled in the system before using this feature.
						</div>
					</div>
				</div>
			</div>
      	</div>
      </div>
	  
	  <!-- DigitalPersona WebSDK Scripts -->
	  <script src="js/es6-shim.js"></script>
	  <script src="js/websdk.client.bundle.min.js"></script>
	  <script src="js/fingerprint.sdk.min.js"></script>
	  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	  <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
	  <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
	  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
	  <script src="js/custom.js"></script>
	  
	  <style>
		/* Fingerprint icon styles */
		.myicon, .verifyicon {
			font-size: 60px;
			display: inline-block;
		}
		.icon-indexfinger-not-enrolled:before,
		.icon-middlefinger-not-enrolled:before {
			content: "👆";
			opacity: 0.3;
			filter: grayscale(100%);
		}
		.icon-indexfinger-enrolled:before,
		.icon-middlefinger-enrolled:before {
			content: "👆";
			color: #28a745;
		}
		.capture-indexfinger:before,
		.capture-middlefinger:before {
			content: "👆";
			color: #007bff;
			animation: pulse 1s infinite;
		}
		@keyframes pulse {
			0%, 100% { opacity: 1; }
			50% { opacity: 0.5; }
		}
	  </style>
	  
	  <script>
	  // Initialize fingerprint reader detection on page load
	  document.addEventListener('DOMContentLoaded', function() {
		  // Check if Fingerprint SDK is loaded
		  if (typeof Fingerprint === 'undefined') {
			  console.error('Fingerprint SDK not loaded');
			  document.getElementById('fp-status').className = 'status-message status-error';
			  document.getElementById('fp-status').innerHTML = '<i class="fas fa-exclamation-triangle"></i> Fingerprint SDK not loaded. Please contact administrator.';
			  document.getElementById('btn-scan-in').disabled = true;
			  document.getElementById('btn-scan-out').disabled = true;
			  return;
		  }
		  
		  // Initialize reader detection for verification
		  try {
			  beginIdentification();
			  document.getElementById('fp-status').className = 'status-message status-success';
			  document.getElementById('fp-status').innerHTML = '<i class="fas fa-check-circle"></i> Reader initialized. Please select a fingerprint reader.';
		  } catch(e) {
			  console.error('Failed to initialize fingerprint reader:', e);
			  document.getElementById('fp-status').className = 'status-message status-error';
			  document.getElementById('fp-status').innerHTML = '<i class="fas fa-exclamation-triangle"></i> Failed to detect fingerprint reader: ' + e.message;
		  }
		  
		  // Setup Time IN button
		  document.getElementById('btn-scan-in').addEventListener('click', function(e) {
			  e.preventDefault();
			  captureForIdentifyIn();
		  });
		  
		  // Setup Time OUT button
		  document.getElementById('btn-scan-out').addEventListener('click', function(e) {
			  e.preventDefault();
			  captureForIdentifyOut();
		  });
	  });
	  
	  // Override showMessage to display in our UI
	  var originalShowMessage = showMessage;
	  showMessage = function(message, message_type) {
		  var statusEl = document.getElementById('fp-status');
		  var resultEl = document.getElementById('fp-result');
		  
		  if (message_type === 'success') {
			  statusEl.className = 'status-message status-success';
			  statusEl.innerHTML = '<i class="fas fa-check-circle"></i> ' + message;
		  } else if (message_type === 'error') {
			  statusEl.className = 'status-message status-error';
			  statusEl.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ' + message;
		  } else {
			  statusEl.className = 'status-message status-info';
			  statusEl.innerHTML = '<i class="fas fa-info-circle"></i> ' + message;
		  }
		  
		  // Call original if it exists
		  if (typeof originalShowMessage === 'function') {
			  originalShowMessage(message, message_type);
		  }
	  };
	  </script>
</body>
</html>
<?php }else{
	header("Location: home.php");
} ?>