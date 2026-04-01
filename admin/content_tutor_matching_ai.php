<?php
// AI-Based Tutor-Student Matching System
$success_message = '';
$error_message = '';

// Handle manual matching acceptance
if (isset($_POST['accept_match'])) {
    $suggestion_id = intval($_POST['suggestion_id']);
    $update_sql = "UPDATE tutor_matching_suggestions SET status = 'Accepted' WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($stmt, "i", $suggestion_id);
    
    if (mysqli_stmt_execute($stmt)) {
        // Get suggestion details
        $get_sql = "SELECT student_id, tutor_id, subject FROM tutor_matching_suggestions WHERE id = ?";
        $get_stmt = mysqli_prepare($conn, $get_sql);
        mysqli_stmt_bind_param($get_stmt, "i", $suggestion_id);
        mysqli_stmt_execute($get_stmt);
        $result = mysqli_stmt_get_result($get_stmt);
        $suggestion = mysqli_fetch_assoc($result);
        
        // Create actual matching
        $match_sql = "INSERT INTO tutor_student_matching (tutor_id, student_id, subject, status, start_date) VALUES (?, ?, ?, 'Active', CURDATE())";
        $match_stmt = mysqli_prepare($conn, $match_sql);
        mysqli_stmt_bind_param($match_stmt, "iis", $suggestion['tutor_id'], $suggestion['student_id'], $suggestion['subject']);
        mysqli_stmt_execute($match_stmt);
        mysqli_stmt_close($match_stmt);
        mysqli_stmt_close($get_stmt);
        
        $success_message = "Match accepted and activated successfully!";
    }
    mysqli_stmt_close($stmt);
}

// Handle match rejection
if (isset($_POST['reject_match'])) {
    $suggestion_id = intval($_POST['suggestion_id']);
    $update_sql = "UPDATE tutor_matching_suggestions SET status = 'Rejected' WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($stmt, "i", $suggestion_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    $success_message = "Match suggestion rejected.";
}

// Generate AI matching suggestions
if (isset($_POST['generate_suggestions'])) {
    $student_id = intval($_POST['student_id']);
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    
    // AI Matching Algorithm
    // Factors: Specialization match, availability, experience, rating
    $tutor_sql = "SELECT t.id, t.tutor_id, CONCAT(t.first_name, ' ', t.last_name) as name, 
                         t.specialization, t.experience_years, t.hourly_rate, t.status
                  FROM tutors t
                  WHERE t.status = 'Active'
                  ORDER BY t.experience_years DESC";
    
    $tutor_result = mysqli_query($conn, $tutor_sql);
    
    while ($tutor = mysqli_fetch_assoc($tutor_result)) {
        $match_score = 0;
        $reasons = [];
        
        // Score based on specialization match
        if (stripos($tutor['specialization'], $subject) !== false) {
            $match_score += 40;
            $reasons[] = "Specializes in " . $tutor['specialization'];
        } else {
            $match_score += 10;
        }
        
        // Score based on experience
        if ($tutor['experience_years'] >= 10) {
            $match_score += 30;
            $reasons[] = "Highly experienced (" . $tutor['experience_years'] . " years)";
        } elseif ($tutor['experience_years'] >= 5) {
            $match_score += 20;
            $reasons[] = "Experienced (" . $tutor['experience_years'] . " years)";
        } else {
            $match_score += 10;
        }
        
        // Score based on availability (active status)
        if ($tutor['status'] == 'Active') {
            $match_score += 20;
            $reasons[] = "Currently available";
        }
        
        // Score based on current workload
        $workload_sql = "SELECT COUNT(*) as count FROM tutor_student_matching WHERE tutor_id = ? AND status = 'Active'";
        $workload_stmt = mysqli_prepare($conn, $workload_sql);
        mysqli_stmt_bind_param($workload_stmt, "i", $tutor['id']);
        mysqli_stmt_execute($workload_stmt);
        $workload_result = mysqli_stmt_get_result($workload_stmt);
        $workload = mysqli_fetch_assoc($workload_result)['count'];
        mysqli_stmt_close($workload_stmt);
        
        if ($workload < 3) {
            $match_score += 10;
            $reasons[] = "Low workload (can give more attention)";
        }
        
        // Only suggest if score is above threshold
        if ($match_score >= 50) {
            $reason_text = implode(", ", $reasons);
            
            // Check if suggestion already exists
            $check_sql = "SELECT id FROM tutor_matching_suggestions WHERE student_id = ? AND tutor_id = ? AND subject = ?";
            $check_stmt = mysqli_prepare($conn, $check_sql);
            mysqli_stmt_bind_param($check_stmt, "iis", $student_id, $tutor['id'], $subject);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            
            if (mysqli_num_rows($check_result) == 0) {
                // Insert suggestion
                $insert_sql = "INSERT INTO tutor_matching_suggestions (student_id, tutor_id, subject, match_score, reason, status) VALUES (?, ?, ?, ?, ?, 'Pending')";
                $insert_stmt = mysqli_prepare($conn, $insert_sql);
                mysqli_stmt_bind_param($insert_stmt, "iisds", $student_id, $tutor['id'], $subject, $match_score, $reason_text);
                mysqli_stmt_execute($insert_stmt);
                mysqli_stmt_close($insert_stmt);
            }
            mysqli_stmt_close($check_stmt);
        }
    }
    
    $success_message = "AI matching suggestions generated successfully!";
}

// Get pending suggestions
$suggestions_sql = "SELECT tms.*, 
                           CONCAT(s.first_name, ' ', s.last_name) as student_name,
                           s.student_id,
                           CONCAT(t.first_name, ' ', t.last_name) as tutor_name,
                           t.tutor_id, t.specialization, t.experience_years, t.hourly_rate
                    FROM tutor_matching_suggestions tms
                    JOIN students s ON tms.student_id = s.id
                    JOIN tutors t ON tms.tutor_id = t.id
                    WHERE tms.status = 'Pending'
                    ORDER BY tms.match_score DESC, tms.created_at DESC";
$suggestions_result = mysqli_query($conn, $suggestions_sql);

// Get students for dropdown
$students_sql = "SELECT id, student_id, CONCAT(first_name, ' ', last_name) as name FROM students WHERE status = 'Active' ORDER BY first_name";
$students_result = mysqli_query($conn, $students_sql);
?>

<style>
    .matching-card {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        padding: 30px;
        margin-bottom: 30px;
    }
    .suggestion-card {
        background: #fff;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        transition: all 0.3s;
    }
    .suggestion-card:hover {
        border-color: #3498db;
        box-shadow: 0 4px 8px rgba(52, 152, 219, 0.2);
    }
    .match-score {
        display: inline-block;
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: bold;
        font-size: 1.1em;
    }
    .score-high {
        background: #d4edda;
        color: #155724;
    }
    .score-medium {
        background: #fff3cd;
        color: #856404;
    }
    .score-low {
        background: #f8d7da;
        color: #721c24;
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
    .form-group {
        margin-bottom: 20px;
    }
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #2c3e50;
    }
    .form-control {
        width: 100%;
        padding: 10px 15px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
    }
    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        margin-right: 10px;
        transition: all 0.3s;
    }
    .btn-primary {
        background: #3498db;
        color: #fff;
    }
    .btn-success {
        background: #27ae60;
        color: #fff;
    }
    .btn-danger {
        background: #e74c3c;
        color: #fff;
    }
    .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
    }
    .tutor-info {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin: 15px 0;
    }
    .info-item {
        display: flex;
        align-items: center;
    }
    .info-item i {
        margin-right: 8px;
        color: #3498db;
    }
</style>

<div class="matching-card">
    <h2><i class="fas fa-brain"></i> AI-Powered Tutor Matching System</h2>
    <p>Intelligent matching based on subject needs, availability, experience, and preferences</p>

    <?php if ($success_message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div style="display:grid;grid-template-columns:2fr 2fr 1fr;gap:15px;align-items:end;">
            <div class="form-group">
                <label for="student_id">Select Student</label>
                <select class="form-control" id="student_id" name="student_id" required>
                    <option value="">-- Select Student --</option>
                    <?php while ($student = mysqli_fetch_assoc($students_result)): ?>
                        <option value="<?php echo $student['id']; ?>">
                            <?php echo htmlspecialchars($student['student_id'] . ' - ' . $student['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="subject">Subject Needed</label>
                <input type="text" class="form-control" id="subject" name="subject" placeholder="e.g., Mathematics, Physics" required>
            </div>

            <div class="form-group">
                <button type="submit" name="generate_suggestions" class="btn btn-primary" style="width:100%;">
                    <i class="fas fa-magic"></i> Generate Matches
                </button>
            </div>
        </div>
    </form>
</div>

<div class="matching-card">
    <h3><i class="fas fa-list-alt"></i> Pending Match Suggestions</h3>
    
    <?php if (mysqli_num_rows($suggestions_result) > 0): ?>
        <?php while ($suggestion = mysqli_fetch_assoc($suggestions_result)): ?>
            <div class="suggestion-card">
                <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:15px;">
                    <div>
                        <h4 style="margin:0;color:#2c3e50;">
                            <i class="fas fa-user-graduate"></i> <?php echo htmlspecialchars($suggestion['student_name']); ?>
                            <i class="fas fa-arrow-right" style="color:#7f8c8d;margin:0 10px;"></i>
                            <i class="fas fa-chalkboard-teacher"></i> <?php echo htmlspecialchars($suggestion['tutor_name']); ?>
                        </h4>
                        <p style="margin:5px 0;color:#7f8c8d;">Subject: <strong><?php echo htmlspecialchars($suggestion['subject']); ?></strong></p>
                    </div>
                    <div>
                        <?php 
                        $score = $suggestion['match_score'];
                        $score_class = $score >= 80 ? 'score-high' : ($score >= 60 ? 'score-medium' : 'score-low');
                        ?>
                        <span class="match-score <?php echo $score_class; ?>">
                            <?php echo number_format($score, 0); ?>% Match
                        </span>
                    </div>
                </div>

                <div class="tutor-info">
                    <div class="info-item">
                        <i class="fas fa-graduation-cap"></i>
                        <span><?php echo htmlspecialchars($suggestion['specialization']); ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-clock"></i>
                        <span><?php echo $suggestion['experience_years']; ?> years experience</span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-dollar-sign"></i>
                        <span>$<?php echo number_format($suggestion['hourly_rate'], 2); ?>/hour</span>
                    </div>
                </div>

                <div style="background:#f8f9fa;padding:12px;border-radius:4px;margin:15px 0;">
                    <strong><i class="fas fa-lightbulb"></i> Why this match?</strong>
                    <p style="margin:5px 0 0 0;"><?php echo htmlspecialchars($suggestion['reason']); ?></p>
                </div>

                <div style="margin-top:15px;">
                    <form method="POST" action="" style="display:inline;">
                        <input type="hidden" name="suggestion_id" value="<?php echo $suggestion['id']; ?>">
                        <button type="submit" name="accept_match" class="btn btn-success btn-sm">
                            <i class="fas fa-check"></i> Accept Match
                        </button>
                    </form>
                    <form method="POST" action="" style="display:inline;">
                        <input type="hidden" name="suggestion_id" value="<?php echo $suggestion['id']; ?>">
                        <button type="submit" name="reject_match" class="btn btn-danger btn-sm">
                            <i class="fas fa-times"></i> Reject
                        </button>
                    </form>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div style="text-align:center;padding:40px;color:#7f8c8d;">
            <i class="fas fa-inbox" style="font-size:3em;margin-bottom:15px;"></i>
            <p>No pending match suggestions. Generate suggestions using the form above.</p>
        </div>
    <?php endif; ?>
</div>
