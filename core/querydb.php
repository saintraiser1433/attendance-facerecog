<?php

namespace fingerprint;

require_once(__DIR__ . "/Database.php");

/**
 * Store fingerprint template for any user type (student, staff, tutor)
 * Combines index and middle finger data into JSON format
 */
function setFingerprintTemplate(
    $user_id,
    $user_type,
    $enrolled_index_finger_fmd_string,
    $enrolled_middle_finger_fmd_string
) {
    $myDatabase = new Database();
    
    // Combine both fingerprints into a JSON structure
    $fingerprint_data = json_encode([
        'index_finger' => $enrolled_index_finger_fmd_string,
        'middle_finger' => $enrolled_middle_finger_fmd_string,
        'enrolled_at' => date('Y-m-d H:i:s')
    ]);
    
    // Check if fingerprint already exists for this user
    $check_sql = "SELECT id FROM fingerprint_templates WHERE user_id = ? AND user_type = ?";
    $existing = $myDatabase->select($check_sql, "is", [$user_id, $user_type]);
    
    if (!empty($existing)) {
        // Update existing fingerprint
        $sql_query = "UPDATE fingerprint_templates SET fingerprint_template = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ? AND user_type = ?";
        $param_type = "sis";
        $param_array = [$fingerprint_data, $user_id, $user_type];
        $myDatabase->update($sql_query, $param_type, $param_array);
        
        // Log the update
        logBiometricAction($user_id, $user_type, 'Enrollment', true, null);
    } else {
        // Insert new fingerprint
        $sql_query = "INSERT INTO fingerprint_templates (user_id, user_type, fingerprint_template) VALUES (?, ?, ?)";
        $param_type = "iss";
        $param_array = [$user_id, $user_type, $fingerprint_data];
        $myDatabase->insert($sql_query, $param_type, $param_array);
        
        // Log the enrollment
        logBiometricAction($user_id, $user_type, 'Enrollment', true, null);
    }
    
    return "success";
}

/**
 * Legacy function for backward compatibility with student enrollment
 */
function setUserFmds(
    $user_id,
    $enrolled_index_finger_fmd_string,
    $enrolled_middle_finger_fmd_string,
    $regfname,
    $reglname,
    $regmname,
    $regyearlevel,
    $regdepartment,
    $regcourse,
    $regblock,
    $regImg
) {
    // Store fingerprint in fingerprint_templates table
    return setFingerprintTemplate($user_id, 'student', $enrolled_index_finger_fmd_string, $enrolled_middle_finger_fmd_string);
}

/**
 * Legacy function for backward compatibility with student update
 */
function setUpdate(
    $user_id,
    $enrolled_index_finger_fmd_string,
    $enrolled_middle_finger_fmd_string,
    $regfname,
    $reglname,
    $regmname,
    $regyearlevel,
    $regdepartment,
    $regcourse,
    $regblock,
    $regImg
) {
    // Update fingerprint in fingerprint_templates table
    return setFingerprintTemplate($user_id, 'student', $enrolled_index_finger_fmd_string, $enrolled_middle_finger_fmd_string);
}

/**
 * Get fingerprint templates for a specific user
 */
function getUserFmds($user_id, $user_type = 'student')
{
    $myDatabase = new Database();
    $sql_query = "SELECT fingerprint_template FROM fingerprint_templates WHERE user_id = ? AND user_type = ?";
    $param_type = "is";
    $param_array = [$user_id, $user_type];
    $result = $myDatabase->select($sql_query, $param_type, $param_array);
    
    if (!empty($result)) {
        $fingerprint_data = json_decode($result[0]['fingerprint_template'], true);
        return json_encode([
            'index_finger' => $fingerprint_data['index_finger'] ?? '',
            'middle_finger' => $fingerprint_data['middle_finger'] ?? ''
        ]);
    }
    
    return json_encode(['index_finger' => '', 'middle_finger' => '']);
}

/**
 * Get user details based on user type
 */
function getUserDetails($user_id, $user_type = 'student')
{
    $myDatabase = new Database();
    
    switch ($user_type) {
        case 'student':
            $sql_query = "SELECT * FROM students WHERE id = ?";
            break;
        case 'staff':
            $sql_query = "SELECT * FROM users WHERE id = ? AND role = 'user'";
            break;
        case 'tutor':
            $sql_query = "SELECT * FROM tutors WHERE id = ?";
            break;
        default:
            return json_encode(null);
    }
    
    $param_type = "i";
    $param_array = [$user_id];
    $user_info = $myDatabase->select($sql_query, $param_type, $param_array);
    return json_encode($user_info);
}

/**
 * Get all fingerprint templates for duplicate checking
 */
function getAllFmds($user_type = null)
{
    $myDatabase = new Database();
    
    if ($user_type) {
        $sql_query = "SELECT user_id, fingerprint_template FROM fingerprint_templates WHERE user_type = ?";
        $allFmds = $myDatabase->select($sql_query, "s", [$user_type]);
    } else {
        $sql_query = "SELECT user_id, user_type, fingerprint_template FROM fingerprint_templates";
        $allFmds = $myDatabase->select($sql_query);
    }
    
    // Parse the JSON templates into index and middle finger arrays
    $parsed_fmds = [];
    if (!empty($allFmds)) {
        foreach ($allFmds as $fmd) {
            $data = json_decode($fmd['fingerprint_template'], true);
            $parsed_fmds[] = [
                'user_id' => $fmd['user_id'],
                'user_type' => $fmd['user_type'] ?? 'student',
                'index_finger' => $data['index_finger'] ?? '',
                'middle_finger' => $data['middle_finger'] ?? ''
            ];
        }
    }
    
    return json_encode($parsed_fmds);
}

/**
 * Get all fingerprints for students enrolled in a specific schedule
 */
function getAll($sched_id)
{
    $myDatabase = new Database();
    
    // Get students enrolled in the schedule and their fingerprints
    $sql_query = "SELECT s.id as student_id, s.student_id as student_number, 
                         CONCAT(s.first_name, ' ', s.last_name) as name,
                         ft.fingerprint_template 
                  FROM students s
                  INNER JOIN fingerprint_templates ft ON s.id = ft.user_id AND ft.user_type = 'student'
                  WHERE s.status = 'Active'";
    
    $allFmds = $myDatabase->select($sql_query);
    
    // Parse the fingerprint templates
    $result = [];
    if (!empty($allFmds)) {
        foreach ($allFmds as $fmd) {
            $data = json_decode($fmd['fingerprint_template'], true);
            $result[] = [
                'student_id' => $fmd['student_number'],
                'name' => $fmd['name'],
                'index_finger' => $data['index_finger'] ?? '',
                'middle_finger' => $data['middle_finger'] ?? ''
            ];
        }
    }
    
    return json_encode($result);
}

/**
 * Get all fingerprints by user type for verification
 * Includes user details (student_id, name, etc.) for proper identification
 */
function getAllByUserType($user_type = 'student')
{
    $myDatabase = new Database();
    
    // First, do a simple direct query to check if any records exist
    $simple_query = "SELECT COUNT(*) as count FROM fingerprint_templates WHERE user_type = ?";
    $simple_result = $myDatabase->select($simple_query, "s", [$user_type]);
    $count = 0;
    if (!empty($simple_result) && is_array($simple_result)) {
        $count = intval($simple_result[0]['count'] ?? 0);
    }
    error_log("getAllByUserType($user_type): Found $count fingerprint records in database");
    
    // Join with appropriate table based on user type to get full user details
    switch ($user_type) {
        case 'student':
            $sql_query = "SELECT ft.user_id, ft.fingerprint_template, ft.user_type,
                                 s.student_id, s.first_name, s.last_name,
                                 CONCAT(s.first_name, ' ', s.last_name) as name
                          FROM fingerprint_templates ft
                          LEFT JOIN students s ON ft.user_id = s.id AND ft.user_type = 'student'
                          WHERE ft.user_type = ?";
            break;
        case 'staff':
            // For verification, get all staff fingerprints (don't filter by status/role here)
            // Status checks should be done when recording attendance, not during verification
            $sql_query = "SELECT ft.user_id, ft.fingerprint_template, ft.user_type,
                                 u.username, u.name
                          FROM fingerprint_templates ft
                          LEFT JOIN users u ON ft.user_id = u.id
                          WHERE ft.user_type = ?";
            break;
        case 'tutor':
            // For verification, get all tutor fingerprints (don't filter by status here)
            // Status checks should be done when recording attendance, not during verification
            $sql_query = "SELECT ft.user_id, ft.fingerprint_template, ft.user_type,
                                 t.tutor_id, t.first_name, t.last_name,
                                 CONCAT(COALESCE(t.first_name, ''), ' ', COALESCE(t.last_name, '')) as name
                          FROM fingerprint_templates ft
                          LEFT JOIN tutors t ON ft.user_id = t.id
                          WHERE ft.user_type = ?";
            break;
        default:
            // Fallback for unknown types
            $sql_query = "SELECT ft.user_id, ft.fingerprint_template, ft.user_type
                          FROM fingerprint_templates ft
                          WHERE ft.user_type = ?";
    }
    
    error_log("getAllByUserType($user_type): Executing query: " . substr($sql_query, 0, 100) . "...");
    
    $allFmds = $myDatabase->select($sql_query, "s", [$user_type]);
    
    // Ensure allFmds is an array (Database::select() may return null if no results)
    if (!is_array($allFmds)) {
        $allFmds = [];
    }
    
    error_log("getAllByUserType($user_type): Database query returned " . count($allFmds) . " raw records");
    
    // If no results from JOIN query, try a simpler direct query (fallback)
    if (empty($allFmds) && $count > 0) {
        error_log("getAllByUserType($user_type): JOIN query returned no results but $count records exist. Trying direct query...");
        $direct_query = "SELECT user_id, fingerprint_template, user_type FROM fingerprint_templates WHERE user_type = ?";
        $allFmds = $myDatabase->select($direct_query, "s", [$user_type]);
        if (!is_array($allFmds)) {
            $allFmds = [];
        }
        error_log("getAllByUserType($user_type): Direct query returned " . count($allFmds) . " records");
    }
    
    // Parse the fingerprint templates
    $result = [];
    if (!empty($allFmds)) {
        foreach ($allFmds as $fmd) {
            // Check if fingerprint_template exists and is not empty
            if (empty($fmd['fingerprint_template'])) {
                error_log("getAllByUserType($user_type): Skipping user_id {$fmd['user_id']} - empty fingerprint_template");
                continue;
            }
            
            $data = json_decode($fmd['fingerprint_template'], true);
            
            // Check if JSON decode failed
            if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                error_log("getAllByUserType($user_type): JSON decode error for user_id {$fmd['user_id']}: " . json_last_error_msg());
                error_log("getAllByUserType($user_type): Template preview: " . substr($fmd['fingerprint_template'], 0, 100));
                continue;
            }
            
            // Check if fingerprint data exists
            $index_finger = $data['index_finger'] ?? '';
            $middle_finger = $data['middle_finger'] ?? '';
            
            if (empty($index_finger) && empty($middle_finger)) {
                error_log("getAllByUserType($user_type): Skipping user_id {$fmd['user_id']} - no fingerprint data in JSON");
                continue;
            }
            
            $record = [
                'user_id' => $fmd['user_id'],
                'user_type' => $fmd['user_type'] ?? $user_type,
                'index_finger' => $index_finger,
                'middle_finger' => $middle_finger
            ];
            
            // Add user-specific fields
            if ($user_type === 'student' && isset($fmd['student_id'])) {
                $record['student_id'] = $fmd['student_id'];
            }
            if (isset($fmd['name'])) {
                $record['name'] = $fmd['name'];
            }
            if (isset($fmd['first_name'])) {
                $record['first_name'] = $fmd['first_name'];
            }
            if (isset($fmd['last_name'])) {
                $record['last_name'] = $fmd['last_name'];
            }
            if ($user_type === 'staff' && isset($fmd['username'])) {
                $record['username'] = $fmd['username'];
            }
            if ($user_type === 'tutor' && isset($fmd['tutor_id'])) {
                $record['tutor_id'] = $fmd['tutor_id'];
            }
            
            $result[] = $record;
        }
    }
    
    error_log("getAllByUserType($user_type): Returning " . count($result) . " valid fingerprint records");
    
    return json_encode($result);
}

/**
 * Log biometric actions (enrollment, verification, etc.)
 */
function logBiometricAction($user_id, $user_type, $action_type, $success, $match_score = null)
{
    try {
        $myDatabase = new Database();
        
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        $device_info = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $sql_query = "INSERT INTO biometric_logs (user_id, user_type, action_type, fingerprint_match_score, success, ip_address, device_info) 
                      VALUES (?, ?, ?, ?, ?, ?, ?)";
        // Parameter types: user_id (i=int), user_type (s=string), action_type (s=string), 
        // match_score (d=double), success (i=int), ip_address (s=string), device_info (s=string)
        $param_type = "issdiss";
        $param_array = [$user_id, $user_type, $action_type, $match_score, $success ? 1 : 0, $ip_address, $device_info];
        
        $result = $myDatabase->insert($sql_query, $param_type, $param_array);
        
        // Silently fail if logging doesn't work - don't break enrollment
        if ($result === -1) {
            error_log("Failed to log biometric action: user_id=$user_id, user_type=$user_type, action=$action_type");
        }
    } catch (\Exception $e) {
        // Silently fail - don't break enrollment if logging fails
        error_log("Error logging biometric action: " . $e->getMessage());
    }
}
