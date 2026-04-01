# Multi-Role Attendance and Information System

## Complete System Documentation

---

## System Overview

A comprehensive PHP-based Attendance and Information System supporting three distinct user roles:
- **Admin** - Full system control and management
- **Teacher** - Attendance management and student monitoring
- **Student** - Self-attendance marking and information access

---

## 1. User Roles & Permissions

### Admin Role
**Access Level:** Full System Control

**Capabilities:**
- ✅ Manage all users (Admin, Teachers, Students)
- ✅ Create and manage announcements
- ✅ View all attendance records
- ✅ Generate comprehensive reports
- ✅ Configure system settings
- ✅ Manage tutors and matching
- ✅ Monitor attendance alerts
- ✅ Access biometric system
- ✅ Export data (Excel/PDF)

**Dashboard:** `admin/dashboard.php`

---

### Teacher Role
**Access Level:** Class & Attendance Management

**Capabilities:**
- ✅ Create attendance sessions
- ✅ Mark student attendance (multiple methods)
- ✅ View own class attendance
- ✅ Post announcements to students
- ✅ Generate class reports
- ✅ Manage session schedules
- ✅ Approve manual attendance
- ✅ View student information

**Dashboard:** `teacher/dashboard.php`

**Features:**
- Create attendance sessions with:
  - Subject and session name
  - Date and time range
  - Department/Year/Section filters
  - Multiple attendance methods
- Mark attendance using:
  - Biometric (when available)
  - PIN entry
  - QR code scanning
  - Manual marking
- View attendance reports
- Manage class schedules

---

### Student Role
**Access Level:** Self-Service & Information Access

**Capabilities:**
- ✅ Mark own attendance
- ✅ View personal attendance history
- ✅ View announcements
- ✅ Access personal QR code
- ✅ Update profile information
- ✅ View attendance statistics
- ✅ Check attendance status

**Dashboard:** `student/dashboard.php`

**Features:**
- Mark attendance using:
  - 6-digit PIN
  - Personal QR code
  - Manual entry (teacher approval required)
  - Biometric (when scanner available)
- View attendance history
- Download personal QR code
- Check attendance percentage
- View class schedules

---

## 2. Alternative Attendance Methods

Since biometric scanners are not yet available, the system supports multiple alternative methods:

### Method 1: PIN Entry
**How it works:**
1. Each user assigned a unique 6-digit PIN
2. Student enters PIN on attendance form
3. System verifies PIN against database
4. Attendance marked if PIN matches

**Security:**
- PIN stored in database
- Encrypted transmission
- Failed attempts logged
- PIN can be reset by admin

**Usage:**
```
Student PIN: 789012
Teacher PIN: 123456
```

---

### Method 2: QR Code
**How it works:**
1. Each student has unique QR code
2. QR code contains encrypted student ID
3. Teacher/System scans QR code
4. Attendance marked automatically

**Features:**
- Unique QR code per student
- Downloadable and printable
- Can be displayed on mobile
- Quick scanning process

**Implementation:**
- QR code stored in database
- Generated on profile creation
- Accessible from student dashboard
- Scannable via webcam or mobile

---

### Method 3: Manual Entry
**How it works:**
1. Student requests manual attendance
2. Request sent to teacher
3. Teacher approves/rejects
4. Attendance marked upon approval

**Use Cases:**
- Technical issues
- Forgotten PIN
- Lost QR code
- Emergency situations

**Workflow:**
```
Student → Request → Teacher Review → Approval → Marked
```

---

### Method 4: Biometric (Future)
**Status:** Ready for integration

**When scanner available:**
- Fingerprint scanning
- Face recognition
- Match score calculation
- Automatic verification

**Current Implementation:**
- Database tables ready
- Code structure prepared
- Simulation mode available
- Easy integration path

---

## 3. Attendance Session System

### Creating Sessions (Teacher)

**Session Details:**
- Session Name (e.g., "Math 101 - Lecture 1")
- Subject
- Date and Time
- Department/Year/Section
- Attendance Method
- Session Code (optional)

**Session Status:**
- **Scheduled** - Future session
- **Active** - Currently accepting attendance
- **Closed** - Attendance period ended
- **Cancelled** - Session cancelled

**Example:**
```
Session: Computer Science 101
Subject: Introduction to Programming
Date: 2025-10-13
Time: 9:00 AM - 11:00 AM
Method: PIN Entry
Status: Active
```

---

### Marking Attendance (Student)

**Process:**
1. View today's active sessions
2. Select session to attend
3. Choose attendance method
4. Complete verification
5. Receive confirmation

**Status Determination:**
- **Present** - Marked within 15 minutes of start time
- **Late** - Marked after 15 minutes
- **Absent** - Not marked
- **Excused** - Teacher-approved absence

---

## 4. Announcement System

### Features
- **Target Audience:** All, Students, Teachers, Admins
- **Priority Levels:** Low, Normal, High, Urgent
- **Pinned Announcements:** Stay at top
- **Attachments:** File upload support
- **Expiry Dates:** Auto-archive old announcements

### Creating Announcements

**Admin Can Post To:**
- All users
- Only students
- Only teachers
- Only admins

**Teacher Can Post To:**
- Own students
- Specific classes
- Department-wide

**Announcement Fields:**
- Title
- Content
- Target Audience
- Priority
- Expiry Date
- Attachments

---

## 5. Database Schema

### Users Table (Enhanced)
```sql
CREATE TABLE users (
    id INT PRIMARY KEY,
    role ENUM('admin','teacher','student'),
    username VARCHAR(255) UNIQUE,
    password VARCHAR(255),
    name VARCHAR(255),
    email VARCHAR(255),
    phone VARCHAR(20),
    student_id VARCHAR(50),
    teacher_id VARCHAR(50),
    department VARCHAR(100),
    year_level VARCHAR(50),
    section VARCHAR(50),
    attendance_pin VARCHAR(6),
    qr_code TEXT,
    status ENUM('Active','Inactive','Suspended')
);
```

### Attendance Sessions Table
```sql
CREATE TABLE attendance_sessions (
    id INT PRIMARY KEY,
    teacher_id INT,
    subject VARCHAR(100),
    session_name VARCHAR(255),
    session_date DATE,
    start_time TIME,
    end_time TIME,
    attendance_method ENUM('Biometric','PIN','QR Code','Manual'),
    session_code VARCHAR(10),
    status ENUM('Scheduled','Active','Closed','Cancelled')
);
```

### User Attendance Table
```sql
CREATE TABLE user_attendance (
    id INT PRIMARY KEY,
    user_id INT,
    session_id INT,
    attendance_date DATE,
    check_in_time DATETIME,
    status ENUM('Present','Absent','Late','Excused'),
    attendance_method ENUM('Biometric','PIN','QR Code','Manual'),
    is_verified BOOLEAN,
    marked_by INT
);
```

### Announcements Table
```sql
CREATE TABLE announcements (
    id INT PRIMARY KEY,
    title VARCHAR(255),
    content TEXT,
    posted_by INT,
    target_audience ENUM('All','Students','Teachers','Admins'),
    priority ENUM('Low','Normal','High','Urgent'),
    is_pinned BOOLEAN,
    expiry_date DATE,
    status ENUM('Active','Archived')
);
```

---

## 6. Login Credentials

### Default Users

**Admin:**
```
Username: admin
Password: admin
Role: Admin (auto-detected)
```

**Teacher:**
```
Username: teacher1
Password: teacher123
Role: Teacher (auto-detected)
Teacher ID: T-2025-01
PIN: 123456
```

**Student:**
```
Username: student1
Password: student123
Role: Student (auto-detected)
Student ID: S-2025-01
PIN: 789012
```

---

## 7. File Structure

```
FINAL.com/
├── index.php                    (Login page - no role selection)
├── home.php                     (Role-based redirect)
├── logout.php                   (Logout handler)
├── db_conn.php                  (Database connection)
│
├── admin/
│   ├── dashboard.php            (Admin dashboard)
│   ├── content_*.php            (Admin features)
│   └── ...
│
├── teacher/
│   ├── dashboard.php            (Teacher dashboard)
│   ├── content_dashboard.php    (Teacher home)
│   ├── content_announcements.php
│   ├── content_create_session.php
│   ├── content_my_sessions.php
│   ├── content_mark_attendance.php
│   ├── content_view_attendance.php
│   ├── content_attendance_reports.php
│   └── content_profile.php
│
├── student/
│   ├── dashboard.php            (Student dashboard)
│   ├── content_dashboard.php    (Student home)
│   ├── content_announcements.php
│   ├── content_mark_attendance.php
│   ├── content_my_attendance.php
│   ├── content_attendance_history.php
│   ├── content_profile.php
│   └── content_qr_code.php
│
└── db/
    ├── setup_database.sql       (Complete database setup)
    └── create_tables.sql        (Table definitions)
```

---

## 8. Setup Instructions

### Step 1: Database Setup
```sql
-- Import the database
SOURCE db/setup_database.sql;

-- Verify tables created
SHOW TABLES;

-- Check default users
SELECT * FROM users;
```

### Step 2: Configuration
1. Update `db_conn.php` with your database credentials
2. Ensure PHP version 7.4 or higher
3. Enable required PHP extensions (mysqli, gd)

### Step 3: Access System
```
1. Navigate to: http://localhost/FINAL.com/FINAL.com/
2. Login with any default user
3. System auto-redirects based on role
```

---

## 9. Usage Workflows

### Teacher Workflow

**Daily Tasks:**
1. Login to teacher dashboard
2. Create attendance session for class
3. Share session code/QR with students
4. Monitor attendance marking
5. Approve manual attendance requests
6. Close session after class

**Weekly Tasks:**
1. Review attendance reports
2. Post announcements
3. Check student attendance patterns
4. Generate weekly reports

---

### Student Workflow

**Daily Tasks:**
1. Login to student dashboard
2. Check today's sessions
3. Mark attendance using preferred method
4. View confirmation
5. Check announcements

**Weekly Tasks:**
1. Review attendance history
2. Check attendance percentage
3. Update profile if needed

---

### Admin Workflow

**Daily Tasks:**
1. Monitor system activity
2. Review attendance alerts
3. Approve user registrations
4. Manage announcements

**Weekly Tasks:**
1. Generate system reports
2. Review attendance statistics
3. Manage user accounts
4. System maintenance

---

## 10. Security Features

### Authentication
- ✅ Role-based access control
- ✅ Automatic role detection
- ✅ Session management
- ✅ Secure password hashing (MD5, upgradable)

### Data Protection
- ✅ SQL injection prevention (prepared statements)
- ✅ XSS protection (output encoding)
- ✅ CSRF protection
- ✅ Input validation and sanitization

### Attendance Security
- ✅ PIN verification
- ✅ QR code encryption
- ✅ Duplicate prevention
- ✅ Time-based validation
- ✅ Audit trail logging

---

## 11. Reporting Features

### Available Reports

**For Admin:**
- System-wide attendance
- User statistics
- Department-wise reports
- Attendance trends
- Alert summaries

**For Teachers:**
- Class attendance
- Student performance
- Session summaries
- Absence patterns

**For Students:**
- Personal attendance
- Attendance percentage
- Monthly summaries
- Status history

### Export Formats
- Excel (.xls)
- PDF (print-ready)
- CSV (data export)

---

## 12. Future Enhancements

### Phase 1 (Ready for Integration)
- [ ] Biometric scanner integration
- [ ] Face recognition
- [ ] Mobile app
- [ ] Push notifications

### Phase 2 (Planned)
- [ ] Email notifications
- [ ] SMS alerts
- [ ] Parent portal
- [ ] Advanced analytics

### Phase 3 (Future)
- [ ] AI-based predictions
- [ ] Blockchain certificates
- [ ] IoT device integration
- [ ] Cloud synchronization

---

## 13. Troubleshooting

### Common Issues

**Issue: Cannot login**
- Solution: Check username/password, verify role in database

**Issue: Attendance not marking**
- Solution: Verify session is active, check PIN/QR code

**Issue: Wrong dashboard appears**
- Solution: Check user role in database, clear session

**Issue: PIN not working**
- Solution: Reset PIN from profile, verify 6 digits

---

## 14. Support & Maintenance

### Regular Maintenance
- Daily: Monitor attendance logs
- Weekly: Database backup
- Monthly: User account review
- Quarterly: Security audit

### Contact Information
- System Admin: admin@school.com
- Technical Support: support@school.com
- Emergency: Available 24/7

---

## 15. Conclusion

This multi-role attendance system provides:

✅ **Three distinct user roles** with appropriate permissions  
✅ **Multiple attendance methods** for flexibility  
✅ **Comprehensive announcement system** for communication  
✅ **Detailed reporting** for all stakeholders  
✅ **Secure and scalable** architecture  
✅ **Ready for biometric integration** when hardware available  

The system is production-ready and can be deployed immediately with alternative attendance methods while waiting for biometric scanner availability.

---

**Version:** 2.0  
**Last Updated:** October 13, 2025  
**Status:** Production Ready
