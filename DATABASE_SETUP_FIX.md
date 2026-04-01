# Database Setup Fix - Preserve Existing Admin

## Changes Made

### Problem
The original `setup_database.sql` would:
- ❌ Use new role enum ('admin','teacher','student')
- ❌ Potentially overwrite existing admin users
- ❌ Conflict with existing database structure

### Solution
Updated `setup_database.sql` to:
- ✅ Keep original role enum: `ENUM('user','admin')`
- ✅ Preserve existing admin users
- ✅ Use `INSERT IGNORE` to avoid conflicts

---

## Key Changes

### 1. Role Enum Restored
**Before:**
```sql
role ENUM('admin','teacher','student') NOT NULL,
```

**After:**
```sql
role ENUM('user','admin') NOT NULL,
```

### 2. Safe User Insertion
**Before:**
```sql
INSERT INTO users (...) VALUES (...)
ON DUPLICATE KEY UPDATE username=username;
```

**After:**
```sql
-- Insert default users only if they don't exist (preserves existing admin)
-- This will NOT delete or overwrite existing users
INSERT IGNORE INTO users (id, role, username, password, name) VALUES
(1, 'admin', 'elias', '81dc9bdb52d04dc20036dbd8313ed055', 'Elias Abdurrahman'),
(2, 'user', 'john', 'e2fc714c4727ee9395f324cd2e7f331f', 'John Doe');
```

### 3. Updated home.php
Added proper handling for 'user' role:
```php
case 'user':
    // Regular user goes to staff dashboard
    header("Location: staff/dashboard.php");
    break;
```

---

## How INSERT IGNORE Works

### INSERT IGNORE Behavior:
- If user with ID 1 exists → **Skip insertion** (preserves existing admin)
- If user with ID 1 doesn't exist → **Insert new user**
- If username 'elias' exists → **Skip insertion** (preserves existing)
- **No errors thrown** on conflicts

### Example:
```sql
-- Existing database has:
-- ID 1: admin 'elias'

-- Running INSERT IGNORE:
INSERT IGNORE INTO users (id, role, username, password, name) VALUES
(1, 'admin', 'elias', '...', 'Elias Abdurrahman');

-- Result: Nothing happens, existing admin preserved ✓
```

---

## Safe Import Process

### Step 1: Backup Current Database
```sql
-- Export current database first
mysqldump -u root -p cuteko > backup_cuteko.sql
```

### Step 2: Import Updated SQL
```sql
-- Import the fixed setup_database.sql
mysql -u root -p cuteko < setup_database.sql
```

### Step 3: Verify Existing Admin
```sql
-- Check that existing admin is still there
SELECT id, role, username, name FROM users WHERE role = 'admin';
```

**Expected Result:**
```
Your existing admin user should still be present with all original data intact
```

---

## Role Structure

### Current System Supports:

**Two Primary Roles:**
1. **admin** - Full system access
2. **user** - Regular user access

**Additional Fields Available:**
- `student_id` - For student identification
- `teacher_id` - For teacher identification
- `department` - Department assignment
- `year_level` - Academic year
- `section` - Class section
- `attendance_pin` - PIN for attendance
- `qr_code` - QR code data

**Note:** While the enum is 'user'/'admin', you can still use the additional fields to differentiate between teachers, students, and staff by using the extra columns.

---

## Login Credentials

### Default Admin (Preserved):
```
Username: elias
Password: 1234
Role: admin
```

### Default User:
```
Username: john
Password: abcd
Role: user
```

---

## Routing Logic

### home.php Routes:
```
admin → admin/dashboard.php
user → staff/dashboard.php
teacher → teacher/dashboard.php (if you add this role later)
student → student/dashboard.php (if you add this role later)
```

---

## Migration Path

### If You Want to Add Teacher/Student Roles Later:

**Option 1: Alter Table (Recommended)**
```sql
ALTER TABLE users 
MODIFY COLUMN role ENUM('user','admin','teacher','student') NOT NULL;
```

**Option 2: Use Existing Structure**
- Keep role as 'user' or 'admin'
- Use `teacher_id` field to identify teachers
- Use `student_id` field to identify students
- Check these fields in your code

**Example:**
```php
if ($_SESSION['role'] == 'user' && !empty($_SESSION['teacher_id'])) {
    // This is a teacher
    header("Location: teacher/dashboard.php");
} elseif ($_SESSION['role'] == 'user' && !empty($_SESSION['student_id'])) {
    // This is a student
    header("Location: student/dashboard.php");
}
```

---

## Testing Checklist

### After Import:

- [ ] Existing admin can still login
- [ ] Admin username unchanged
- [ ] Admin password still works
- [ ] No duplicate users created
- [ ] All existing data preserved
- [ ] New tables created successfully
- [ ] No SQL errors during import

### Test Commands:
```sql
-- Check users table
SELECT * FROM users;

-- Verify admin exists
SELECT * FROM users WHERE role = 'admin';

-- Check for duplicates
SELECT username, COUNT(*) FROM users GROUP BY username HAVING COUNT(*) > 1;
```

---

## Summary

✅ **Role Enum:** Changed back to `ENUM('user','admin')`  
✅ **Existing Admin:** Preserved using `INSERT IGNORE`  
✅ **No Overwrites:** Existing data remains intact  
✅ **Safe Import:** Can be run multiple times without issues  
✅ **Backward Compatible:** Works with existing login system  

**Status:** Ready to import safely! Your existing admin will not be deleted or modified.
