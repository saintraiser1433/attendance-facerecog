# Student Image Directory

## Purpose
This directory stores profile pictures for users (students, staff, and tutors) displayed during attendance verification.

## Default Image Setup

1. Create or download a default profile image (e.g., silhouette or avatar)
2. Name it `default.png`
3. Place it in this directory
4. Recommended size: 200x200 pixels (or larger, square aspect ratio)

## User Image Naming Convention

User profile images should be named according to their user ID or reference:
- Students: `{student_id}.png` or `{student_id}.jpg`
- Staff: `{staff_id}.png` or `{staff_id}.jpg`
- Tutors: `{tutor_id}.png` or `{tutor_id}.jpg`

## Supported Formats
- PNG (recommended)
- JPG/JPEG
- GIF
- WebP

## File Permissions
Ensure this directory has write permissions for PHP:
- Windows: Set appropriate folder permissions for WAMP/XAMPP user
- Linux: `chmod 755 /path/to/lib/studentimage`

## Security Notes
1. **File size limit**: Recommend max 2MB per image
2. **File validation**: Validate file types before upload
3. **Directory protection**: Consider adding .htaccess if using Apache:
   ```apache
   # .htaccess in lib/studentimage/
   <FilesMatch "\.(php|php3|php4|php5|phtml)$">
     Deny from all
   </FilesMatch>
   ```

## Usage in Code

The attendance system displays images from this directory:

```javascript
// In custom.js
$("#studimg").attr("src", "../lib/studentimage/" + attendanceResult.img);
```

```php
// In insertAttendance.php
'img' => $student['profile_picture'] ?? 'default.png'
```

## Creating Default Image (Quick Method)

### Using ImageMagick
```bash
convert -size 200x200 xc:gray -pointsize 72 -fill white -gravity center -annotate +0+0 "?" default.png
```

### Using PHP GD Library
```php
<?php
$img = imagecreatetruecolor(200, 200);
$bg = imagecolorallocate($img, 200, 200, 200);
imagefill($img, 0, 0, $bg);
$textColor = imagecolorallocate($img, 100, 100, 100);
imagestring($img, 5, 80, 90, 'No Image', $textColor);
imagepng($img, 'default.png');
imagedestroy($img);
?>
```

### Download Free Avatar
Or download a free default avatar from:
- https://www.flaticon.com/free-icons/avatar
- https://www.freepik.com/free-photos-vectors/default-avatar
- https://ui-avatars.com/api/?name=Default&size=200

## Troubleshooting

### Image Not Displaying
1. Check file exists: `lib/studentimage/[filename]`
2. Verify file permissions (readable by web server)
3. Check console for 404 errors
4. Verify image path in database matches actual filename

### Broken Image Icon Shows
1. Ensure `default.png` exists
2. Check database `profile_picture` column values
3. Verify web server can read the file
4. Check for typos in filenames

## Database References

Profile picture filenames are stored in:
- `students.profile_picture`
- `users.profile_picture` (for staff)
- `tutors.profile_picture`

Example query to check:
```sql
SELECT id, first_name, last_name, profile_picture 
FROM students 
WHERE profile_picture IS NOT NULL;
```

## Upload Feature Integration

If implementing image upload feature, ensure:
1. File type validation (only images)
2. File size limits (recommend 2MB max)
3. Filename sanitization
4. Unique filename generation
5. Database update with new filename
6. Old file cleanup (if replacing)

Example upload handler:
```php
if ($_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $filename = $_FILES['profile_picture']['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if (in_array($ext, $allowed)) {
        $newname = $user_id . '.' . $ext;
        move_uploaded_file(
            $_FILES['profile_picture']['tmp_name'],
            'lib/studentimage/' . $newname
        );
        
        // Update database
        $stmt = $conn->prepare("UPDATE students SET profile_picture = ? WHERE id = ?");
        $stmt->bind_param("si", $newname, $user_id);
        $stmt->execute();
    }
}
```

