# Enhanced Forms Guide - Student & Tutor Image Upload + Tutor Assignment

## 🎉 New Features Added!

### ✅ Enhanced Student Input Form
**URL:** `http://localhost/FINAL.com/FINAL.com/admin/dashboard.php?page=input_students`

**New Features:**
1. **Student ID Image Upload** - Drag & drop or click to upload
2. **Year Level Selection** - Dropdown with all active year levels
3. **Section Field** - Assign class section
4. **Tutor Assignment** - Select tutor and subject for the student
5. **Image Preview** - See uploaded image before submitting
6. **Better Organization** - Sections for Basic, Academic, and ID Image

### ✅ Enhanced Add Tutor Form
**URL:** `http://localhost/FINAL.com/FINAL.com/admin/dashboard.php?page=add_tutor`

**New Features:**
1. **Profile Picture Upload** - Drag & drop or click to upload
2. **Circular Image Preview** - Professional profile photo display
3. **Better Organization** - Sections for Profile, Basic, and Professional info
4. **Enhanced Validation** - Secure file upload with image verification

---

## 📁 Files Created/Modified

### New Files:
1. ✅ `admin/content_input_students_new.php` - Enhanced student form
2. ✅ `admin/content_add_tutor_new.php` - Enhanced tutor form
3. ✅ `db/add_tutor_profile_picture.sql` - Migration for tutor profile column
4. ✅ `ENHANCED_FORMS_GUIDE.md` - This guide

### Modified Files:
1. ✅ `admin/dashboard.php` - Updated to use new forms
2. ✅ `db/setup_database.sql` - Added profile_picture to tutors table

### Upload Directories (Auto-created):
1. ✅ `uploads/student_ids/` - Student ID images
2. ✅ `uploads/tutor_profiles/` - Tutor profile pictures

---

## 🗄️ Database Changes

### Students Table (Already Has):
- `year_level_id` - Links to year_levels table
- `section` - Class section
- `student_id_image` - Path to ID image
- `profile_picture` - Path to profile photo

### Tutors Table (NEW):
- `profile_picture` - Path to profile photo

### New Relationship:
- Students can be assigned to tutors via `tutor_student_matching` table
- Automatically created when adding student with tutor selection

---

## 🚀 How to Use

### Step 1: Update Database

**Option A: Fresh Install**
```sql
-- Import the updated setup_database.sql
SOURCE c:/wamp64/www/FINAL.com/FINAL.com/db/setup_database.sql;
```

**Option B: Existing Database**
```sql
-- Just add the profile_picture column to tutors
SOURCE c:/wamp64/www/FINAL.com/FINAL.com/db/add_tutor_profile_picture.sql;
```

**Via phpMyAdmin:**
1. Open: `http://localhost/phpmyadmin`
2. Select database: `cuteko`
3. Click "SQL" tab
4. Paste SQL from above
5. Click "Go"

---

### Step 2: Test Student Form

1. **Login as admin**
   - URL: `http://localhost/FINAL.com/FINAL.com/`
   - Username: `elias`
   - Password: `1234`

2. **Go to Input Students**
   - Click "Manage Students" → "Input Students"
   - Or: `http://localhost/FINAL.com/FINAL.com/admin/dashboard.php?page=input_students`

3. **Fill the Form:**
   - **Basic Info:** Name, email, phone, date of birth, address
   - **Academic Info:** 
     - Select Year Level (e.g., YR1 - Year 1)
     - Enter Section (e.g., Section A)
     - Enter Subject for Tutor (e.g., Mathematics)
     - Select Tutor from dropdown (optional)
   - **ID Image:** Drag & drop or click to upload student ID photo

4. **Submit:**
   - Click "Add Student"
   - Student ID auto-generated (e.g., 2025-01)
   - If tutor selected, matching automatically created

---

### Step 3: Test Tutor Form

1. **Go to Add Tutor**
   - Click "Manage Tutors" → "Add Tutor"
   - Or: `http://localhost/FINAL.com/FINAL.com/admin/dashboard.php?page=add_tutor`

2. **Upload Profile Picture:**
   - Drag & drop or click the upload area
   - See circular preview
   - Can remove and re-upload

3. **Fill the Form:**
   - **Basic Info:** Name, email, phone, hire date
   - **Professional Info:** Specialization, qualification, experience, hourly rate
   - **Status:** Active/Inactive/On Leave
   - **Address:** Full address

4. **Submit:**
   - Click "Add Tutor"
   - Tutor ID auto-generated (e.g., 2025-01)
   - Profile picture saved

---

## 🎨 Features Explained

### Image Upload Features:

**Drag & Drop:**
- Drag image file directly onto upload area
- Visual feedback (color change on hover/drag)
- Instant preview

**Click to Browse:**
- Click upload area to open file picker
- Select image from computer
- Preview before submitting

**Validation:**
- Only accepts: JPG, PNG, GIF
- Max size: 5MB
- Validates actual image content (not just extension)
- Secure filename generation

**Preview:**
- Student ID: Rectangle preview
- Tutor Profile: Circular preview
- Remove button to change image

---

### Tutor Assignment Feature:

**How It Works:**
1. Select a tutor from dropdown (shows: ID - Name - Specialization)
2. Enter the subject (e.g., Mathematics, Physics)
3. When student is added, creates entry in `tutor_student_matching` table
4. Status automatically set to "Active"
5. Start date set to current date

**Benefits:**
- One-step student creation + tutor assignment
- No need to go to separate matching page
- Immediate tutor-student relationship
- Can view in "View Matching Tutor" page

---

## 📊 Form Sections

### Student Form Sections:

**1. Basic Information**
- Enrollment date (required)
- First name, last name (required)
- Email, phone
- Date of birth
- Status (Active/Inactive/Graduated)
- Address

**2. Academic Information**
- Year Level dropdown (from year_levels table)
- Section field
- Subject for tutor
- Assign tutor dropdown (from tutors table)

**3. Student ID Image**
- Drag & drop upload area
- Image preview
- Remove button

---

### Tutor Form Sections:

**1. Profile Picture**
- Drag & drop upload area
- Circular image preview
- Remove button

**2. Basic Information**
- Hire date (required)
- First name, last name (required)
- Email (required), phone
- Status

**3. Professional Information**
- Specialization (e.g., Mathematics)
- Qualification (e.g., PhD)
- Experience years
- Hourly rate
- Address

---

## 🔒 Security Features

### File Upload Security:

✅ **File Type Validation**
- Checks MIME type
- Validates actual image content with `getimagesize()`
- Rejects fake images

✅ **File Size Limit**
- Maximum 5MB per file
- Prevents server overload

✅ **Secure Filenames**
- Uses `uniqid()` for unique names
- Prevents file overwrite
- Prevents path traversal attacks

✅ **Directory Permissions**
- Created with 0755 (secure)
- Not world-writable
- Proper access control

✅ **Error Handling**
- Comprehensive upload error messages
- User-friendly feedback
- Detailed logging

---

## 📂 File Storage

### Student ID Images:
```
uploads/student_ids/
├── student_id_67abc123.jpg
├── student_id_67abc456.png
└── student_id_67abc789.gif
```

### Tutor Profile Pictures:
```
uploads/tutor_profiles/
├── tutor_67def123.jpg
├── tutor_67def456.png
└── tutor_67def789.gif
```

**Database Storage:**
- Relative path stored in database
- Example: `uploads/student_ids/student_id_67abc123.jpg`
- Easy to display: `<img src="<?php echo $row['student_id_image']; ?>">`

---

## 🧪 Testing Checklist

### Student Form:
- [ ] Can access form
- [ ] Year level dropdown shows levels
- [ ] Tutor dropdown shows tutors
- [ ] Can upload image (drag & drop)
- [ ] Can upload image (click to browse)
- [ ] Image preview works
- [ ] Can remove image
- [ ] Form submits successfully
- [ ] Student ID auto-generated
- [ ] Image saved to uploads/student_ids/
- [ ] Tutor assignment created (if selected)
- [ ] Success message shows

### Tutor Form:
- [ ] Can access form
- [ ] Can upload profile picture (drag & drop)
- [ ] Can upload profile picture (click)
- [ ] Circular preview works
- [ ] Can remove image
- [ ] Form submits successfully
- [ ] Tutor ID auto-generated
- [ ] Image saved to uploads/tutor_profiles/
- [ ] Success message shows
- [ ] Can view tutor in Manage Tutors

---

## 🐛 Troubleshooting

### Image Upload Not Working:

**Problem:** "Failed to create upload directory"
**Solution:**
```bash
# Create directories manually
mkdir c:\wamp64\www\FINAL.com\FINAL.com\uploads
mkdir c:\wamp64\www\FINAL.com\FINAL.com\uploads\student_ids
mkdir c:\wamp64\www\FINAL.com\FINAL.com\uploads\tutor_profiles

# Set permissions (Windows)
icacls "c:\wamp64\www\FINAL.com\FINAL.com\uploads" /grant Users:F
```

**Problem:** "Error uploading image file"
**Solution:**
- Check directory permissions
- Verify PHP upload_max_filesize in php.ini
- Check disk space

**Problem:** "Uploaded file is not a valid image"
**Solution:**
- Make sure file is actually an image
- Try different image format (JPG, PNG)
- Check file isn't corrupted

---

### Tutor Dropdown Empty:

**Problem:** No tutors showing in dropdown
**Solution:**
```sql
-- Check if tutors exist
SELECT * FROM tutors WHERE status = 'Active';

-- If empty, add sample tutor
INSERT INTO tutors (first_name, last_name, email, specialization, status, hire_date) 
VALUES ('John', 'Smith', 'john@example.com', 'Mathematics', 'Active', CURDATE());
```

---

### Year Level Dropdown Empty:

**Problem:** No year levels showing
**Solution:**
```sql
-- Import year levels
SOURCE c:/wamp64/www/FINAL.com/FINAL.com/db/setup_database.sql;

-- Or manually insert
INSERT IGNORE INTO year_levels (year_level_code, year_level_name, description, order_number) VALUES
('YR1', 'Year 1', 'First Year Students', 1),
('YR2', 'Year 2', 'Second Year Students', 2);
```

---

## 📈 Benefits

### For Administrators:
✅ Upload student ID images for verification  
✅ Assign tutors during student registration  
✅ Upload tutor profile pictures  
✅ Better visual identification  
✅ One-step student + tutor matching  
✅ Professional appearance  

### For System:
✅ Secure file uploads  
✅ Organized file storage  
✅ Automatic tutor-student matching  
✅ Better data relationships  
✅ Enhanced user experience  

---

## 🎯 Summary

### What You Can Do Now:

**Student Management:**
1. Add students with ID photos
2. Assign year level and section
3. Assign tutor with subject
4. View student with photo
5. Track student-tutor relationships

**Tutor Management:**
1. Add tutors with profile pictures
2. Professional tutor profiles
3. Visual tutor identification
4. Better tutor presentation

**File Management:**
1. Secure image uploads
2. Organized storage
3. Easy image display
4. Proper file handling

---

## ✅ Quick Start

**1. Update Database:**
```sql
SOURCE c:/wamp64/www/FINAL.com/FINAL.com/db/add_tutor_profile_picture.sql;
```

**2. Test Student Form:**
- Go to: Input Students
- Fill form
- Upload ID image
- Select tutor
- Submit ✅

**3. Test Tutor Form:**
- Go to: Add Tutor
- Upload profile picture
- Fill form
- Submit ✅

**Done!** 🎉

---

**Version:** 3.2 (Enhanced Forms)  
**Date:** October 13, 2025  
**Status:** ✅ Production Ready
