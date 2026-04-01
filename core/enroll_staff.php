<?php
/**
 * Staff and Tutor Fingerprint Enrollment
 * Handles fingerprint enrollment for staff and tutors from admin panel
 */

namespace fingerprint;

// Start output buffering to prevent any output before JSON
ob_start();

require(__DIR__ . "/querydb.php");
require_once(__DIR__ . "/helpers/helpers.php");

// Clean any output that might have been generated
ob_clean();
header('Content-Type: application/json');

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'POST request required']);
    exit();
}

// Get POST data - can come from form or JSON
$post_data = $_POST;
if (empty($post_data) && !empty(file_get_contents('php://input'))) {
    $post_data = json_decode(file_get_contents('php://input'), true);
}

// Validate required fields
if (empty($post_data['user_id']) || empty($post_data['user_type']) || empty($post_data['data'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields: user_id, user_type, and data']);
    exit();
}

$user_id = intval($post_data['user_id']);
$user_type = $post_data['user_type']; // 'staff' or 'tutor'
$fingerprint_data = json_decode($post_data['data'], true);

// Validate user type
if (!in_array($user_type, ['staff', 'tutor', 'student'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid user type. Must be staff, tutor, or student']);
    exit();
}

// Extract fingerprint samples
$index_finger_samples = $fingerprint_data['index_finger'] ?? [];
$middle_finger_samples = $fingerprint_data['middle_finger'] ?? [];

// Validate we have samples
if (empty($index_finger_samples) || empty($middle_finger_samples)) {
    echo json_encode(['success' => false, 'error' => 'Both index and middle finger samples are required']);
    exit();
}

// Prepare fingerprint array for enrollment
$pre_reg_fmd_array = [
    "index_finger" => $index_finger_samples,
    "middle_finger" => $middle_finger_samples
];

try {
    // Check for duplicates (optional)
    $is_duplicate = false;
    if (!empty($index_finger_samples[0])) {
        $is_duplicate = isDuplicate($index_finger_samples[0], $user_type);
    }
    
    if ($is_duplicate) {
        echo json_encode(['success' => false, 'error' => 'Duplicate fingerprint detected. This fingerprint is already enrolled.']);
        exit();
    }
    
    // Process fingerprint enrollment
    $json_response = enroll_fingerprint($pre_reg_fmd_array);
    $response = json_decode($json_response);
    
    if ($response !== "enrollment failed" && !empty($response)) {
        $enrolled_index_finger = $response->enrolled_index_finger ?? '';
        $enrolled_middle_finger = $response->enrolled_middle_finger ?? '';
        
        // Store in database
        $result = setFingerprintTemplate(
            $user_id,
            $user_type,
            $enrolled_index_finger,
            $enrolled_middle_finger
        );
        
        if ($result === "success") {
            echo json_encode([
                'success' => true,
                'message' => ucfirst($user_type) . ' fingerprint enrolled successfully',
                'user_id' => $user_id,
                'user_type' => $user_type
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to save fingerprint to database']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Fingerprint enrollment processing failed']);
    }
    
} catch (\Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}

/**
 * Check if fingerprint is already enrolled
 */
function isDuplicate($fmd_to_check_string, $user_type = null)
{
    $allFmds = json_decode(getAllFmds($user_type));

    if (!$allFmds || empty($allFmds)) {
        return false;
    }

    $enrolled_hand_array = $allFmds;

    $json_response = is_duplicate_fingerprint($fmd_to_check_string, $enrolled_hand_array);
    $response = json_decode($json_response);

    return (bool) $response;
}


