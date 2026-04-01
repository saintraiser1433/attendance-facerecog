# Quick Fix: Year Level Column Error

## Error Message
```
Fatal error: Unknown column 'year_level_id' in 'where clause'
```

## Problem
The `students` table doesn't have the new columns (`year_level_id`, `section`, `student_id_image`) yet.

---

## ✅ Solution (Choose One)

### **Option 1: Run Migration Script (RECOMMENDED)** ⭐

This safely adds the columns without affecting existing data.

**Steps:**
1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Select database: `cuteko`
3. Click "SQL" tab
4. Copy and paste the contents of: `db/migrate_add_year_level_columns.sql`
5. Click "Go"
6. Wait for success message
7. Refresh your browser

**OR via Command Line:**
```bash
mysql -u root -p cuteko < c:/wamp64/www/FINAL.com/FINAL.com/db/migrate_add_year_level_columns.sql
```

---

### **Option 2: Import Full Database**

This recreates the entire database (⚠️ will delete existing data).

**Steps:**
1. **Backup first!**
   ```bash
   mysqldump -u root -p cuteko > backup_cuteko.sql
   ```

2. Import setup_database.sql:
   ```bash
   mysql -u root -p cuteko < c:/wamp64/www/FINAL.com/FINAL.com/db/setup_database.sql
   ```

3. Refresh browser

---

### **Option 3: Manual SQL (Quick)**

Run these SQL commands in phpMyAdmin:

```sql
USE cuteko;

-- Add year_level_id column
ALTER TABLE students ADD COLUMN year_level_id INT NULL AFTER address;

-- Add section column
ALTER TABLE students ADD COLUMN section VARCHAR(50) NULL AFTER year_level_id;

-- Add student_id_image column
ALTER TABLE students ADD COLUMN student_id_image VARCHAR(255) NULL AFTER section;

-- Add profile_picture column
ALTER TABLE students ADD COLUMN profile_picture VARCHAR(255) NULL AFTER student_id_image;

-- Add foreign key
ALTER TABLE students ADD CONSTRAINT fk_students_year_level 
FOREIGN KEY (year_level_id) REFERENCES year_levels(id) ON DELETE SET NULL;

-- Add indexes
CREATE INDEX idx_year_level ON students(year_level_id);
```

---

## ✅ Verification

After applying the fix, verify:

1. **Check columns exist:**
   ```sql
   DESCRIBE students;
   ```
   Should show: `year_level_id`, `section`, `student_id_image`, `profile_picture`

2. **Access the page:**
   ```
   http://localhost/FINAL.com/FINAL.com/admin/dashboard.php?page=manage_year_levels
   ```
   Should load without errors ✅

3. **Test functionality:**
   - Add a year level
   - Edit a year level
   - View student count (should show 0)

---

## 🔧 What Was Fixed

### Code Changes:
1. ✅ `content_manage_year_levels.php` - Now checks if column exists before querying
2. ✅ Added graceful fallback if columns don't exist
3. ✅ Created migration script for safe database update

### Database Changes:
1. ✅ Added `year_level_id` column to students
2. ✅ Added `section` column to students
3. ✅ Added `student_id_image` column to students
4. ✅ Added `profile_picture` column to students
5. ✅ Added foreign key constraint
6. ✅ Added indexes for performance

---

## 📊 Before vs After

### Before (Error):
```
students table:
- id
- student_id
- first_name
- last_name
- email
- phone
- date_of_birth
- address
- enrollment_date
- status
```

### After (Fixed):
```
students table:
- id
- student_id
- first_name
- last_name
- email
- phone
- date_of_birth
- address
- year_level_id ← NEW
- section ← NEW
- student_id_image ← NEW
- profile_picture ← NEW
- enrollment_date
- status
```

---

## 🚨 Troubleshooting

### Error: "Table 'year_levels' doesn't exist"
**Solution:** Run the migration script first, it creates the table.

### Error: "Cannot add foreign key constraint"
**Solution:** 
1. Make sure year_levels table exists
2. Check if there's existing data with invalid year_level_id values
3. Run: `UPDATE students SET year_level_id = NULL WHERE year_level_id NOT IN (SELECT id FROM year_levels);`

### Error: "Duplicate column name"
**Solution:** Column already exists, you're good! Just refresh browser.

---

## ⏱️ Time Required

- **Option 1 (Migration):** 1 minute
- **Option 2 (Full Import):** 2 minutes
- **Option 3 (Manual SQL):** 2 minutes

---

## ✅ Status After Fix

Once fixed, you'll be able to:
- ✅ Access Manage Year Levels page
- ✅ Add/Edit/Delete year levels
- ✅ See student count per year level
- ✅ Add students with year level selection
- ✅ Upload student ID images

---

## 📞 Still Having Issues?

1. Check MySQL error log
2. Verify database connection in `db_conn.php`
3. Make sure you're using database `cuteko`
4. Try restarting MySQL service
5. Clear browser cache

---

**Quick Command to Fix Everything:**
```bash
# One command to rule them all
mysql -u root -p cuteko < c:/wamp64/www/FINAL.com/FINAL.com/db/migrate_add_year_level_columns.sql
```

Then refresh your browser! ✅
