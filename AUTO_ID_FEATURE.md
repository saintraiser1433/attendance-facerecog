# Auto-Generated ID Feature

## Overview
Student IDs and Tutor IDs are now **automatically generated** based on the current year in the format: **YYYY-NN**

## Format Examples
- **2025-01** (First student/tutor in 2025)
- **2025-02** (Second student/tutor in 2025)
- **2025-03** (Third student/tutor in 2025)
- **2026-01** (First student/tutor in 2026)

## How It Works

### Database Triggers
Two MySQL triggers have been created:
1. `before_insert_student` - Auto-generates student_id
2. `before_insert_tutor` - Auto-generates tutor_id

### Trigger Logic
```sql
-- Example for students:
1. Check if student_id is NULL or empty
2. Get current year (e.g., 2025)
3. Find the highest number used for this year
4. Increment by 1
5. Format as YYYY-NN (e.g., 2025-01)
```

### Sequential Numbering
- Numbers reset each year (2025-99 → 2026-01)
- Numbers are padded with zeros (01, 02, 03... 99)
- Can handle up to 99 entries per year
- If you need more, the format automatically extends (100, 101, etc.)

## Usage

### Adding a New Student
**Before (Old Way):**
```php
// Had to manually enter student_id
INSERT INTO students (student_id, first_name, last_name, ...) 
VALUES ('STU001', 'John', 'Doe', ...);
```

**After (New Way):**
```php
// student_id is auto-generated
INSERT INTO students (first_name, last_name, ...) 
VALUES ('John', 'Doe', ...);
// Result: student_id = '2025-01' (automatically)
```

### Adding a New Tutor
**Before (Old Way):**
```php
// Had to manually enter tutor_id
INSERT INTO tutors (tutor_id, first_name, last_name, ...) 
VALUES ('TUT001', 'Jane', 'Smith', ...);
```

**After (New Way):**
```php
// tutor_id is auto-generated
INSERT INTO tutors (first_name, last_name, ...) 
VALUES ('Jane', 'Smith', ...);
// Result: tutor_id = '2025-01' (automatically)
```

## Form Changes

### Student Form (`content_input_students.php`)
- ✅ Removed manual Student ID input field
- ✅ Added info message about auto-generation
- ✅ Updated validation to not require student_id
- ✅ Updated INSERT query to exclude student_id

### Tutor Form (`content_add_tutor.php`)
- ✅ Removed manual Tutor ID input field
- ✅ Added info message about auto-generation
- ✅ Updated validation to not require tutor_id
- ✅ Updated INSERT query to exclude tutor_id

## Benefits

1. **No Duplicate IDs**: Database ensures uniqueness
2. **No Manual Entry**: Reduces human error
3. **Year-Based Organization**: Easy to identify when someone joined
4. **Sequential**: Easy to track total entries per year
5. **Consistent Format**: All IDs follow same pattern

## Examples in Action

### Scenario 1: Adding Students in 2025
```
First student:  2025-01
Second student: 2025-02
Third student:  2025-03
...
99th student:   2025-99
100th student:  2025-100
```

### Scenario 2: New Year (2026)
```
First student in 2026:  2026-01  (resets to 01)
Second student in 2026: 2026-02
```

### Scenario 3: Mixed Years
```
Students added in 2025: 2025-01, 2025-02, 2025-03
Students added in 2026: 2026-01, 2026-02
Students added in 2025: 2025-04 (continues from last 2025 number)
```

## Database Tables Affected

### Students Table
```sql
student_id VARCHAR(50) UNIQUE  -- No longer NOT NULL, auto-generated
```

### Tutors Table
```sql
tutor_id VARCHAR(50) UNIQUE  -- No longer NOT NULL, auto-generated
```

## Testing the Feature

### Test 1: Add a Student
1. Go to Admin Dashboard → Input Students
2. Fill in name, email, enrollment date
3. Click "Add Student"
4. Check database: `SELECT student_id FROM students ORDER BY id DESC LIMIT 1;`
5. Should see: `2025-01` (or current year)

### Test 2: Add Multiple Students
1. Add 3 students in a row
2. Check database: `SELECT student_id FROM students ORDER BY id DESC LIMIT 3;`
3. Should see: `2025-03`, `2025-02`, `2025-01`

### Test 3: Add a Tutor
1. Go to Admin Dashboard → Add Tutor
2. Fill in required fields (no tutor_id needed)
3. Click "Add Tutor"
4. Check database: `SELECT tutor_id FROM tutors ORDER BY id DESC LIMIT 1;`
5. Should see: `2025-01` (or current year)

## Troubleshooting

### Issue: IDs not generating
**Solution:** Make sure triggers are created
```sql
SHOW TRIGGERS LIKE 'students';
SHOW TRIGGERS LIKE 'tutors';
```

### Issue: Duplicate ID error
**Solution:** This shouldn't happen with triggers, but if it does:
```sql
-- Check for existing IDs
SELECT student_id FROM students WHERE student_id LIKE '2025-%';
-- Manually fix if needed
UPDATE students SET student_id = '2025-XX' WHERE id = YY;
```

### Issue: Wrong year in ID
**Solution:** Check server date/time
```sql
SELECT YEAR(CURDATE());  -- Should return current year
```

## Migration Notes

### Existing Data
- Old IDs (like 'STU001', 'TUT001') will remain unchanged
- New entries will use the new format (2025-01, 2025-02)
- Both formats can coexist in the database

### If You Want to Regenerate All IDs
```sql
-- Backup first!
-- Then update all student IDs
SET @counter = 0;
UPDATE students 
SET student_id = CONCAT(YEAR(enrollment_date), '-', LPAD(@counter := @counter + 1, 2, '0'))
ORDER BY id;
```

## Files Modified

1. **db/setup_database.sql** - Added triggers
2. **db/create_tables.sql** - Added triggers
3. **admin/content_add_tutor.php** - Removed tutor_id field
4. **admin/content_input_students.php** - Removed student_id field

## Summary

✅ **Student IDs**: Auto-generated as YYYY-NN (e.g., 2025-01)  
✅ **Tutor IDs**: Auto-generated as YYYY-NN (e.g., 2025-01)  
✅ **No manual entry required**  
✅ **Year-based sequential numbering**  
✅ **Automatic and error-free**
