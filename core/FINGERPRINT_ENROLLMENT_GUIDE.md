# Fingerprint Enrollment Guide

## Database Schema

### Table: `fingerprint_templates`

Stores fingerprint data for all user types (students, staff, tutors):

```sql
CREATE TABLE fingerprint_templates (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  user_type ENUM('student', 'staff', 'tutor') NOT NULL,
  fingerprint_template LONGTEXT NOT NULL,
  fingerprint_image LONGBLOB,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_user_fingerprint (user_id, user_type)
);
```

### Table: `biometric_logs`

Logs all biometric activities:

```sql
CREATE TABLE biometric_logs (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  user_type ENUM('student', 'staff', 'tutor') NOT NULL,
  action_type ENUM('Check-In', 'Check-Out', 'Enrollment', 'Verification Failed') NOT NULL,
  fingerprint_match_score DECIMAL(5,2),
  success TINYINT(1) NOT NULL,
  ip_address VARCHAR(45),
  device_info TEXT,
  timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

## How Fingerprint Data is Stored

### Fingerprint Template Structure

The `fingerprint_template` column stores a JSON-encoded object containing both index and middle finger data:

```json
{
  "index_finger": "BASE64_ENCODED_INDEX_FINGER_TEMPLATE_DATA",
  "middle_finger": "BASE64_ENCODED_MIDDLE_FINGER_TEMPLATE_DATA",
  "enrolled_at": "2025-11-05 12:30:45"
}
```

### Why JSON Format?

1. **Flexibility**: Easy to add more fingers or metadata in the future
2. **Single Record**: One row per user instead of multiple rows
3. **Simplicity**: Simple queries to get all fingerprint data
4. **Efficiency**: Better database performance with fewer joins

## Enrollment Process Flow

### Step 1: Capture Fingerprints

The DigitalPersona SDK captures multiple samples:
- **2 samples** of the index finger
- **2 samples** of the middle finger

```javascript
// From custom.js
Hand.addIndexFingerSample(sample);
Hand.addMiddleFingerSample(sample);
```

### Step 2: Generate Template

Multiple samples are processed into a single template per finger:

```php
// From helpers.php
$json_response = enroll_fingerprint($pre_reg_fmd_array);
```

This creates:
- `enrolled_index_finger` - Combined template from 2 index finger samples
- `enrolled_middle_finger` - Combined template from 2 middle finger samples

### Step 3: Store in Database

```php
// From querydb.php
setFingerprintTemplate(
    $user_id,           // User's ID in students/users/tutors table
    $user_type,         // 'student', 'staff', or 'tutor'
    $index_template,    // Processed index finger template
    $middle_template    // Processed middle finger template
);
```

### Step 4: Log the Enrollment

```php
logBiometricAction(
    $user_id,
    $user_type,
    'Enrollment',
    true,              // Success
    null               // No match score for enrollment
);
```

## Core PHP Files and Functions

### 1. `querydb.php` - Database Operations

#### Main Functions:

**`setFingerprintTemplate($user_id, $user_type, $index_finger, $middle_finger)`**
- Stores or updates fingerprint data
- Creates JSON structure
- Handles INSERT or UPDATE automatically
- Logs the action

**`getUserFmds($user_id, $user_type)`**
- Retrieves fingerprint data for a specific user
- Returns JSON with index and middle finger templates

**`getAllByUserType($user_type)`**
- Gets all fingerprints for a specific user type
- Used during verification

**`logBiometricAction($user_id, $user_type, $action, $success, $score)`**
- Records all biometric activities
- Tracks IP address and device info

### 2. `enroll.php` - Student Enrollment (Legacy)

Handles student enrollment from the original system:
- Processes JSON POST data
- Checks for duplicates
- Calls `setFingerprintTemplate()` internally

### 3. `enroll_staff.php` - Staff/Tutor Enrollment

New endpoint for admin panel enrollment:
- Validates user_id and user_type
- Processes fingerprint samples
- Returns JSON response
- Handles errors gracefully

### 4. `verify.php` - Fingerprint Verification

Matches captured fingerprint against enrolled templates:
- Supports all user types
- Logs successful and failed attempts
- Returns match information

## Enrollment Examples

### Example 1: Enroll a Student

```javascript
// From admin panel
const data = {
    id: studentId,
    index_finger: [sample1, sample2],
    middle_finger: [sample3, sample4],
    regfname: "John",
    reglname: "Doe",
    // ... other fields
};

$.post('../core/enroll.php', { data: JSON.stringify(data) }, function(response) {
    if (response === 'success') {
        alert('Student enrolled successfully');
    }
});
```

**Database Result:**
```sql
INSERT INTO fingerprint_templates (user_id, user_type, fingerprint_template)
VALUES (4, 'student', '{"index_finger":"...","middle_finger":"...","enrolled_at":"2025-11-05 12:30:45"}');

INSERT INTO biometric_logs (user_id, user_type, action_type, success)
VALUES (4, 'student', 'Enrollment', 1);
```

### Example 2: Enroll a Staff Member

```javascript
// From admin/content_enroll_staff_fingerprint.php
const data = {
    user_id: 5,
    user_type: 'staff',
    data: JSON.stringify({
        index_finger: [sample1, sample2],
        middle_finger: [sample3, sample4]
    })
};

$.post('../core/enroll_staff.php', data, function(response) {
    if (response.success) {
        alert('Staff member enrolled: ' + response.message);
    }
});
```

**Database Result:**
```sql
INSERT INTO fingerprint_templates (user_id, user_type, fingerprint_template)
VALUES (5, 'staff', '{"index_finger":"...","middle_finger":"...","enrolled_at":"2025-11-05 12:35:22"}');

INSERT INTO biometric_logs (user_id, user_type, action_type, success)
VALUES (5, 'staff', 'Enrollment', 1);
```

### Example 3: Update Existing Fingerprint

If a user already has fingerprints enrolled, the system automatically updates:

```php
// Automatically detects existing record
if (!empty($existing)) {
    // UPDATE instead of INSERT
    UPDATE fingerprint_templates 
    SET fingerprint_template = ?, updated_at = CURRENT_TIMESTAMP 
    WHERE user_id = ? AND user_type = ?
}
```

## Verification Process

### How Verification Works:

1. **Capture** fingerprint sample
2. **Retrieve** all enrolled fingerprints for that user type
3. **Compare** captured sample against each enrolled template
4. **Return** match if score exceeds threshold

```php
// From verify.php
foreach ($hand_data as $value) {
    $enrolled_fingers = [
        "index_finger" => $value->index_finger,
        "middle_finger" => $value->middle_finger
    ];
    
    $result = verify_fingerprint($captured_sample, $enrolled_fingers);
    
    if ($result === "match") {
        // Log successful verification
        logBiometricAction($user_id, $user_type, 'Check-In', true, 95.0);
        // Record attendance
        // ...
    }
}
```

## Data Retrieval Examples

### Get Student Fingerprints

```php
$user_id = 4;
$fingerprints = getUserFmds($user_id, 'student');
$data = json_decode($fingerprints);

// Access fingerprints
$index_finger = $data->index_finger;
$middle_finger = $data->middle_finger;
```

### Get All Staff Fingerprints

```php
$all_staff = getAllByUserType('staff');
$staff_list = json_decode($all_staff);

foreach ($staff_list as $staff) {
    echo "User ID: " . $staff->user_id;
    echo "Index Finger: " . substr($staff->index_finger, 0, 50) . "...";
}
```

### Check Enrollment History

```sql
SELECT bl.*, 
       CASE 
           WHEN bl.user_type = 'student' THEN CONCAT(s.first_name, ' ', s.last_name)
           WHEN bl.user_type = 'staff' THEN u.name
           WHEN bl.user_type = 'tutor' THEN CONCAT(t.first_name, ' ', t.last_name)
       END as user_name
FROM biometric_logs bl
LEFT JOIN students s ON bl.user_id = s.id AND bl.user_type = 'student'
LEFT JOIN users u ON bl.user_id = u.id AND bl.user_type = 'staff'
LEFT JOIN tutors t ON bl.user_id = t.id AND bl.user_type = 'tutor'
WHERE bl.action_type = 'Enrollment'
ORDER BY bl.timestamp DESC;
```

## Security Best Practices

1. **Never Store Raw Fingerprints**: Only store processed templates
2. **Use HTTPS**: Always encrypt data in transit
3. **Log Everything**: Track all enrollment and verification attempts
4. **Validate User Types**: Ensure user_type matches actual user records
5. **Check Duplicates**: Prevent same fingerprint from being enrolled multiple times
6. **Limit Access**: Only admins can enroll fingerprints
7. **Audit Logs**: Regularly review biometric_logs for suspicious activity

## Troubleshooting

### Issue: "Duplicate fingerprint detected"

**Cause**: The fingerprint is already enrolled for another user

**Solution**: 
```sql
-- Find which user has this fingerprint
SELECT user_id, user_type FROM fingerprint_templates 
WHERE fingerprint_template LIKE '%PARTIAL_TEMPLATE_STRING%';
```

### Issue: Fingerprint not saving

**Check**:
1. Database connection is active
2. User exists in corresponding table (students/users/tutors)
3. user_type is valid ('student', 'staff', 'tutor')
4. Fingerprint template is not empty

**Debug**:
```php
// Add error logging
error_log("Enrollment attempt: user_id=$user_id, type=$user_type");
error_log("Template length: " . strlen($fingerprint_data));
```

### Issue: Verification always fails

**Check**:
1. Fingerprint is actually enrolled
2. User type matches
3. Fingerprint reader is working
4. Templates are not corrupted

**Test Query**:
```sql
SELECT user_id, user_type, 
       LENGTH(fingerprint_template) as template_size,
       created_at, updated_at
FROM fingerprint_templates
WHERE user_id = YOUR_USER_ID;
```

## Migration from Old Schema

If you have old data in separate `index_finger` and `middle_finger` columns:

```sql
-- Migrate student fingerprints
INSERT INTO fingerprint_templates (user_id, user_type, fingerprint_template)
SELECT id, 'student',
       JSON_OBJECT(
           'index_finger', index_finger,
           'middle_finger', middle_finger,
           'enrolled_at', NOW()
       )
FROM old_student_table
WHERE index_finger IS NOT NULL AND index_finger != '';
```

## Performance Optimization

1. **Index** the user_id and user_type columns
2. **Limit** queries to specific user types when possible
3. **Cache** fingerprint data in memory for active sessions
4. **Compress** template data if storage is a concern
5. **Archive** old biometric_logs regularly

```sql
-- Add indexes for better performance
CREATE INDEX idx_fingerprint_user ON fingerprint_templates(user_id, user_type);
CREATE INDEX idx_biometric_timestamp ON biometric_logs(timestamp DESC);
CREATE INDEX idx_biometric_user ON biometric_logs(user_id, user_type);
```

