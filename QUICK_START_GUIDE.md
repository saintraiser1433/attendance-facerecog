# Quick Start Guide - Year Level & Image Upload Features

## 🚀 Getting Started in 5 Minutes

### Step 1: Update Database (2 minutes)

**Option A: Using phpMyAdmin**
1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Select database: `cuteko`
3. Click "Import" tab
4. Choose file: `db/setup_database.sql`
5. Click "Go"
6. Wait for success message

**Option B: Using MySQL Command Line**
```bash
mysql -u root -p cuteko < c:/wamp64/www/FINAL.com/FINAL.com/db/setup_database.sql
```

### Step 2: Verify Installation (1 minute)

**Check Tables:**
```sql
-- Should see year_levels table
SHOW TABLES LIKE 'year_levels';

-- Should see 5 default year levels
SELECT * FROM year_levels;

-- Check students table has new columns
DESCRIBE students;
```

**Expected Output:**
- ✅ year_levels table exists
- ✅ 5 year levels (YR1-YR5) present
- ✅ students table has: year_level_id, section, student_id_image

### Step 3: Access Features (1 minute)

**Login as Admin:**
```
URL: http://localhost/FINAL.com/FINAL.com/
Username: elias
Password: 1234
```

**Navigate to Features:**
1. **Manage Year Levels:** Admin Dashboard → Manage Students → Manage Year Levels
2. **Add Students:** Admin Dashboard → Manage Students → Input Students

### Step 4: Test Year Level Management (1 minute)

**Add a Test Year Level:**
1. Go to Manage Year Levels
2. Fill form:
   - Code: TEST
   - Name: Test Year
   - Order: 99
   - Description: Testing
3. Click "Add Year Level"
4. See it in the table
5. Click "Edit" to modify
6. Click "Delete" to remove (if no students)

### Step 5: Test Student with Image (2 minutes)

**Add a Test Student:**
1. Go to Input Students
2. Fill required fields:
   - First Name: John
   - Last Name: Doe
   - Year Level: Select "Year 1 (YR1)"
   - Enrollment Date: Today's date
3. Upload ID Image:
   - Click upload area
   - Select any image (JPG/PNG)
   - See preview
4. Click "Add Student"
5. Check "Recently Added Students" table
6. See student with image thumbnail

---

## 📋 Quick Reference

### Default Year Levels
| Code | Name | Order |
|------|------|-------|
| YR1  | Year 1 | 1 |
| YR2  | Year 2 | 2 |
| YR3  | Year 3 | 3 |
| YR4  | Year 4 | 4 |
| YR5  | Year 5 | 5 |

### File Upload Specs
- **Allowed Types:** JPG, PNG, GIF
- **Max Size:** 5MB
- **Storage:** `uploads/student_ids/`
- **Naming:** `student_id_[unique_id].[ext]`

### Menu Locations
```
Admin Dashboard
├── Manage Students
│   ├── Input Students (Enhanced with image upload)
│   ├── Manage Year Levels (NEW)
│   ├── Enroll Fingerprint
│   └── View Attendance
```

---

## ✅ Verification Checklist

After setup, verify these work:

- [ ] Can access Manage Year Levels page
- [ ] Can see 5 default year levels
- [ ] Can add new year level
- [ ] Can edit existing year level
- [ ] Can delete year level (with no students)
- [ ] Year level dropdown shows in Add Student form
- [ ] Can upload student ID image
- [ ] Image preview shows before submit
- [ ] Can add student with year level and image
- [ ] Recently added students show thumbnails
- [ ] Student count per year level displays correctly

---

## 🆘 Quick Troubleshooting

### Problem: Year levels not showing
**Fix:** Import setup_database.sql again

### Problem: Can't upload images
**Fix:** 
```php
// Check directory exists
if (!file_exists('uploads/student_ids')) {
    mkdir('uploads/student_ids', 0777, true);
}
```

### Problem: Foreign key error
**Fix:** Ensure year_levels table exists before adding students

### Problem: Image not displaying
**Fix:** Check file path starts with `../` in display code

---

## 🎯 Next Steps

1. **Customize Year Levels:** Add/edit to match your institution
2. **Import Students:** Use the enhanced form to add students
3. **Upload ID Images:** Collect and upload student ID photos
4. **Organize by Section:** Use section field for class organization
5. **Generate Reports:** Filter students by year level

---

## 📞 Support

For detailed documentation, see:
- `YEAR_LEVEL_AND_IMAGE_UPLOAD_FEATURE.md` - Complete feature guide
- `MULTI_ROLE_SYSTEM_DOCUMENTATION.md` - System overview
- `DATABASE_SETUP_FIX.md` - Database information

**Status:** ✅ Ready to Use!
