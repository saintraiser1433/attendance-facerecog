# ID Display Feature Update

## Overview
The forms now **show the auto-generated IDs** both before and after creation.

## What's New

### 1. **Preview Next ID** (Before Submission)
When you open the form, you'll see a blue info box showing what ID will be assigned:

**Example:**
```
ℹ Auto-Generated ID: The next student will receive ID: 2025-01
```

This updates automatically after each submission.

### 2. **Display Generated ID** (After Submission)
After successfully adding a student or tutor, the success message shows the actual generated ID:

**Example:**
```
✓ Student added successfully!
  Generated Student ID: 2025-01
```

## Visual Examples

### Add Tutor Form

**Before Adding:**
```
┌─────────────────────────────────────────────────────┐
│ ℹ Auto-Generated ID: The next tutor will receive   │
│   ID: 2025-01                                       │
└─────────────────────────────────────────────────────┘

[Form fields: Name, Email, etc.]
```

**After Adding:**
```
┌─────────────────────────────────────────────────────┐
│ ✓ Tutor added successfully!                         │
│   Generated Tutor ID: 2025-01                       │
│   View all tutors                                   │
└─────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│ ℹ Auto-Generated ID: The next tutor will receive   │
│   ID: 2025-02                                       │
└─────────────────────────────────────────────────────┘
```

### Add Student Form

**Before Adding:**
```
┌─────────────────────────────────────────────────────┐
│ ℹ Auto-Generated ID: The next student will receive │
│   ID: 2025-01                                       │
└─────────────────────────────────────────────────────┘

[Form fields: Name, Email, etc.]
```

**After Adding:**
```
┌─────────────────────────────────────────────────────┐
│ ✓ Student added successfully!                       │
│   Generated Student ID: 2025-01                     │
└─────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│ ℹ Auto-Generated ID: The next student will receive │
│   ID: 2025-02                                       │
└─────────────────────────────────────────────────────┘
```

## How It Works

### 1. **Next ID Calculation**
When the form loads, PHP queries the database:
```php
// Get the highest number for current year
SELECT MAX(CAST(SUBSTRING(student_id, 6) AS UNSIGNED)) + 1
FROM students 
WHERE student_id LIKE '2025-%'
```

Result: `2025-01` (if no students exist for 2025)

### 2. **ID Retrieval After Insert**
After inserting a new record:
```php
// Get the auto-generated ID
$last_id = mysqli_insert_id($conn);
SELECT student_id FROM students WHERE id = $last_id
```

### 3. **ID Recalculation**
After successful insert, the next ID is recalculated:
```php
// Update the preview for next submission
$next_student_id = '2025-02'  // Incremented
```

## Features

### ✅ Real-Time Preview
- Shows what ID will be assigned **before** you submit
- Updates immediately after each submission
- No guessing what ID will be used

### ✅ Confirmation Display
- Shows the **actual generated ID** after creation
- Large, bold text for easy visibility
- Green color to indicate success

### ✅ Sequential Tracking
- First student: `2025-01`
- Second student: `2025-02`
- Third student: `2025-03`
- And so on...

## User Experience Flow

### Adding First Student of 2025

1. **Open Form**
   - See: "Next student will receive ID: **2025-01**"

2. **Fill Form**
   - Enter name, email, etc.
   - No need to enter ID

3. **Submit**
   - Click "Add Student"

4. **See Result**
   - Success message: "Student added successfully!"
   - Generated ID: "**2025-01**"
   - Next preview updates to: "**2025-02**"

### Adding Second Student

1. **Form Already Shows**
   - "Next student will receive ID: **2025-02**"

2. **Fill & Submit**
   - Enter details and submit

3. **See Result**
   - Generated ID: "**2025-02**"
   - Next preview: "**2025-03**"

## Benefits

### 1. **Transparency**
Users know exactly what ID will be assigned before submitting

### 2. **Verification**
After submission, users can verify the correct ID was generated

### 3. **No Surprises**
The preview and actual ID match (unless someone else submits simultaneously)

### 4. **Easy Record Keeping**
Users can note down the ID immediately after creation

## Technical Details

### Files Modified
1. **content_add_tutor.php**
   - Added `$next_tutor_id` calculation
   - Added `$generated_tutor_id` retrieval
   - Updated success message to show ID
   - Updated info box to show next ID

2. **content_input_students.php**
   - Added `$next_student_id` calculation
   - Added `$generated_student_id` retrieval
   - Updated success message to show ID
   - Updated info box to show next ID

### Code Snippets

**Calculate Next ID:**
```php
$year_prefix = date('Y');
$next_id_sql = "SELECT COALESCE(MAX(CAST(SUBSTRING(student_id, 6) AS UNSIGNED)), 0) + 1 as next_num 
                FROM students 
                WHERE student_id LIKE CONCAT('$year_prefix', '-%')";
$next_id_result = mysqli_query($conn, $next_id_sql);
$next_id_row = mysqli_fetch_assoc($next_id_result);
$next_student_id = $year_prefix . '-' . str_pad($next_id_row['next_num'], 2, '0', STR_PAD_LEFT);
```

**Retrieve Generated ID:**
```php
$last_id = mysqli_insert_id($conn);
$get_id_sql = "SELECT student_id FROM students WHERE id = ?";
$id_stmt = mysqli_prepare($conn, $get_id_sql);
mysqli_stmt_bind_param($id_stmt, "i", $last_id);
mysqli_stmt_execute($id_stmt);
$id_result = mysqli_stmt_get_result($id_stmt);
$id_row = mysqli_fetch_assoc($id_result);
$generated_student_id = $id_row['student_id'];
```

**Display in Form:**
```php
<!-- Preview -->
<div class="alert" style="background-color: #d1ecf1;">
    <strong>Auto-Generated ID:</strong> The next student will receive ID: 
    <strong><?php echo htmlspecialchars($next_student_id); ?></strong>
</div>

<!-- Success Message -->
<?php if ($generated_student_id): ?>
    <strong>Generated Student ID: 
        <span style="font-size:1.2em;"><?php echo htmlspecialchars($generated_student_id); ?></span>
    </strong>
<?php endif; ?>
```

## Testing

### Test Scenario 1: First Entry
1. Open "Add Student" form
2. Should see: "Next student will receive ID: **2025-01**"
3. Fill form and submit
4. Should see: "Generated Student ID: **2025-01**"
5. Preview should update to: "**2025-02**"

### Test Scenario 2: Multiple Entries
1. Add 3 students in a row
2. Should see IDs: 2025-01, 2025-02, 2025-03
3. Preview should show: 2025-04 for next entry

### Test Scenario 3: Year Change
1. If testing in 2026
2. Should see: 2026-01 (not 2025-XX)
3. Numbering resets each year

## Summary

✅ **Preview ID** before submission  
✅ **Display generated ID** after submission  
✅ **Auto-update** next ID preview  
✅ **Large, visible** ID display  
✅ **User-friendly** experience  

Users now have complete visibility into the ID generation process!
