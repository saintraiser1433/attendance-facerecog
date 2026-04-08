<?php

namespace fingerprint;

require_once(__DIR__ . "/helpers/helpers.php");
require_once(__DIR__ . "/querydb.php");

/**
 * Verify fingerprint against enrolled templates
 * Supports all user types: student, staff, tutor
 */

if (!empty($_POST["data"])) {
    $user_data = json_decode($_POST["data"]);
    
    // Handle both object and array formats
    $captured_index_samples = [];
    $captured_middle_samples = [];
    if (is_array($user_data)) {
        $sched = $user_data['sched'] ?? null;
        $user_type = $user_data['user_type'] ?? null;
        $captured_index_samples = (isset($user_data['index_finger']) && is_array($user_data['index_finger'])) ? $user_data['index_finger'] : [];
        $captured_middle_samples = (isset($user_data['middle_finger']) && is_array($user_data['middle_finger'])) ? $user_data['middle_finger'] : [];
        $pre_reg_fmd_string = $captured_index_samples[0] ?? ($captured_middle_samples[0] ?? null);
    } else {
        $sched = $user_data->sched ?? null;
        $user_type = $user_data->user_type ?? null;
        $captured_index_samples = (isset($user_data->index_finger) && is_array($user_data->index_finger)) ? $user_data->index_finger : [];
        $captured_middle_samples = (isset($user_data->middle_finger) && is_array($user_data->middle_finger)) ? $user_data->middle_finger : [];
        $pre_reg_fmd_string = $captured_index_samples[0] ?? ($captured_middle_samples[0] ?? null);
    }
    
    if (!$pre_reg_fmd_string) {
        echo json_encode([
            'match' => false,
            'error' => 'No fingerprint data provided'
        ]);
        exit();
    }

    // Flatten captured samples for fallback array-format matching
    $captured_samples = [];
    foreach (array_merge($captured_index_samples, $captured_middle_samples) as $s) {
        if (is_string($s) && trim($s) !== '') {
            $captured_samples[] = $s;
        }
    }
    
    // Get fingerprint data based on context
    $hand_data = [];
    
    // If user_type is explicitly provided, use specific lookup
    if ($user_type) {
        // For specific user type verification
        $hand_data = json_decode(getAllByUserType($user_type), true) ?? [];
        error_log("Verification: Using specific user type lookup (user_type=$user_type)");
    } 
    // For general verification (login page) - ALWAYS search all user types
    // This ensures staff and tutors can login even if sched is set to a default value
    else {
        // Search all user types (student, staff, tutor)
        // Note: We don't use getAll($sched) here because it only returns students
        // On the login page, we need to check all user types regardless of schedule
        error_log("Verification: Searching all user types (student, staff, tutor) - sched=$sched, user_type=$user_type");
        $student_data = json_decode(getAllByUserType('student'), true) ?? [];
        $staff_data = json_decode(getAllByUserType('staff'), true) ?? [];
        $tutor_data = json_decode(getAllByUserType('tutor'), true) ?? [];
        
        error_log("Verification: Found " . count($student_data) . " students, " . count($staff_data) . " staff, " . count($tutor_data) . " tutors");
        
        $hand_data = array_merge($student_data, $staff_data, $tutor_data);
    }
    
    // Debug: Log how many records we're checking
    error_log("Verification: Checking " . count($hand_data) . " enrolled fingerprints total");
    
    // Debug: Log user types found
    $user_types_found = [];
    foreach ($hand_data as $record) {
        $ut = is_array($record) ? ($record['user_type'] ?? 'unknown') : ($record->user_type ?? 'unknown');
        if (!in_array($ut, $user_types_found)) {
            $user_types_found[] = $ut;
        }
    }
    error_log("Verification: User types in data: " . implode(', ', $user_types_found));
    
    // If no enrolled fingerprints found, return error immediately
    if (empty($hand_data)) {
        error_log("Verification: No enrolled fingerprints found in database");
        echo json_encode([
            'match' => false,
            'error' => 'No fingerprints enrolled in the system. Please enroll your fingerprint first.'
        ]);
        exit();
    }
    
    $match_found = false;
    $matched_user = null;
    $matched_user_type = null;
    $best_score = 0;
    
    if (!empty($hand_data)) {
        foreach ($hand_data as $key => $value) {
            // Handle both object and array formats
            $user_id_check = is_array($value) ? ($value['user_id'] ?? null) : ($value->user_id ?? null);
            $index_finger = is_array($value) ? ($value['index_finger'] ?? '') : ($value->index_finger ?? '');
            $middle_finger = is_array($value) ? ($value['middle_finger'] ?? '') : ($value->middle_finger ?? '');
            
            // Support both formats:
            // - Engine format: string template per finger
            // - Fallback format: array of captured samples per finger (exact match against captured sample)
            if (is_array($index_finger) || is_array($middle_finger)) {
                $idx_arr = is_array($index_finger) ? $index_finger : [];
                $mid_arr = is_array($middle_finger) ? $middle_finger : [];
                $exact_match = false;
                foreach ($captured_samples as $cap) {
                    if (in_array($cap, $idx_arr, true) || in_array($cap, $mid_arr, true)) {
                        $exact_match = true;
                        break;
                    }
                }
                if ($exact_match) {
                    $match_found = true;
                    $matched_user = $value;
                    $matched_user_type = is_array($value) ? ($value['user_type'] ?? 'student') : ($value->user_type ?? 'student');
                    $matched_user_id = $user_id_check ? intval($user_id_check) : 0;

                    logBiometricAction($matched_user_id, $matched_user_type, 'Verification', true, 98.5);

                    echo json_encode([
                        'match' => true,
                        'user_id' => $matched_user_id,
                        'user_type' => $matched_user_type
                    ]);
                    exit();
                }

                error_log("Verification: Skipping user_id $user_id_check - fallback array format (no exact match)");
                continue;
            }
            
            $enrolled_fingers = [
                "index_finger" => $index_finger,
                "middle_finger" => $middle_finger
            ];

            if (empty($enrolled_fingers["index_finger"])) {
                error_log("Verification: Skipping user_id $user_id_check - empty fingerprint data");
                continue; // Skip if no fingerprint data
            }

            // Debug: Log verification attempt
            $user_type_check = is_array($value) ? ($value['user_type'] ?? 'unknown') : ($value->user_type ?? 'unknown');
            error_log("Verification: Checking user_id $user_id_check (user_type: $user_type_check)");
            error_log("Verification: Index finger length: " . strlen($enrolled_fingers['index_finger']));
            error_log("Verification: Middle finger length: " . strlen($enrolled_fingers['middle_finger']));

            try {
                $json_response = verify_fingerprint($pre_reg_fmd_string, $enrolled_fingers);
                
                // Check if service is unavailable
                if (empty($json_response) || $json_response === false) {
                    error_log("Verification: Fingerprint service unavailable or no response");
                    continue; // Try next fingerprint
                }
                
                // Debug: Log raw response
                error_log("Verification response for user_id $user_id_check: " . substr($json_response, 0, 100));
                
                // Handle different response formats
                $response = json_decode($json_response, true);
            } catch (\Exception $e) {
                error_log("Verification error for user_id $user_id_check: " . $e->getMessage());
                continue; // Try next fingerprint
            }
            
            // Check if response is a string (old format)
            if ($json_response === "match" || $json_response === '"match"') {
                $is_match = true;
            } 
            // Check if response is JSON with match field
            else if (is_array($response) && isset($response['match']) && $response['match'] === true) {
                $is_match = true;
            }
            // Check if response is JSON object with match property
            else if (is_object($response) && isset($response->match) && $response->match === true) {
                $is_match = true;
            }
            // Check if response is just the string "match"
            else if ($response === "match" || (is_string($response) && trim($response) === "match")) {
                $is_match = true;
            }
            else {
                $is_match = false;
            }
            
            if ($is_match) {
                $match_found = true;
                $matched_user = $value;
                
                // Determine user type from the matched record
                if (is_array($value)) {
                    $matched_user_type = $value['user_type'] ?? 'student';
                    // user_id should be the numeric ID from fingerprint_templates.user_id
                    // This is the actual ID in students/users/tutors table
                    $matched_user_id = isset($value['user_id']) && $value['user_id'] > 0 ? intval($value['user_id']) : 0;
                } else {
                    $matched_user_type = $value->user_type ?? 
                                       (isset($value->student_id) ? 'student' : 
                                       (isset($value->staff_id) ? 'staff' : 'tutor'));
                    // user_id should be the numeric ID
                    $matched_user_id = isset($value->user_id) && $value->user_id > 0 ? intval($value->user_id) : 0;
                }
                
                // If user_id is still 0, try to get it from the database using student_id
                if ($matched_user_id == 0 && $matched_user_type === 'student') {
                    $student_id_str = is_array($value) ? ($value['student_id'] ?? null) : ($value->student_id ?? null);
                    if ($student_id_str) {
                        // Look up the numeric ID from students table using the Database class
                        $myDatabase = new Database();
                        $lookup_sql = "SELECT id FROM students WHERE student_id = ? LIMIT 1";
                        $lookup_result = $myDatabase->select($lookup_sql, "s", [$student_id_str]);
                        if (!empty($lookup_result) && isset($lookup_result[0]['id'])) {
                            $matched_user_id = intval($lookup_result[0]['id']);
                        }
                    }
                }
                
                // Log successful verification
                logBiometricAction(
                    $matched_user_id,
                    $matched_user_type,
                    'Verification',
                    true,
                    95.0
                );
                
                // Return the matched user info
                $return_data = [
                    'match' => true,
                    'user_id' => $matched_user_id,
                    'user_type' => $matched_user_type
                ];
                
                // Add additional fields if available
                if (is_array($value)) {
                    $return_data['student_id'] = $value['student_id'] ?? null;
                    $return_data['name'] = $value['name'] ?? ($value['first_name'] ?? '') . ' ' . ($value['last_name'] ?? '') ?? 'Unknown';
                } else {
                    $return_data['student_id'] = $value->student_id ?? null;
                    $return_data['name'] = $value->name ?? ($value->first_name ?? '') . ' ' . ($value->last_name ?? '') ?? 'Unknown';
                }
                
                echo json_encode($return_data);
                exit();
            }
        }
    }
    
    if (!$match_found) {
        // Log failed verification
        logBiometricAction(
            0, // Unknown user
            $user_type ?? 'unknown',
            'Verification Failed',
            false,
            null
        );
        
        echo json_encode([
            'match' => false,
            'error' => 'No matching fingerprint found. Please ensure your fingerprint is enrolled in the system.'
        ]);
    }
} else {
    echo json_encode([
        'match' => false,
        'error' => 'POST request with "data" field required'
    ]);
}
