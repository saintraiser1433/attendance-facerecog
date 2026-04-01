# Enrollment Pages Fix - FingerprintManager Error

## Problem
Both student and staff/tutor fingerprint enrollment pages were showing the error:
```
Error: FingerprintManager is not defined
```

This occurred because the pages were trying to use a non-existent `FingerprintManager` object instead of using the working DigitalPersona SDK integration from `custom.js`.

## Solution
Updated both enrollment pages to use the same working pattern as `index.php` (login page):

1. **Replaced script includes** - Changed from `fingerprint_handler.js` (which doesn't exist) to the correct SDK scripts:
   - `es6-shim.js`
   - `websdk.client.bundle.min.js`
   - `fingerprint.sdk.min.js`
   - `custom.js` (contains all the working functions)

2. **Removed FingerprintManager references** - Replaced all `FingerprintManager` calls with functions from `custom.js`:
   - `beginEnrollment()` - Initializes device detection
   - `beginCapture()` - Starts fingerprint capture
   - `serverEnroll()` - Saves fingerprint template

3. **Added proper error handling** - Added checks to ensure Fingerprint SDK is loaded before initializing

4. **Updated UI elements** - Added fingerprint icon displays matching the login page

## Files Modified

### 1. `admin/content_enroll_student_fingerprint.php`
- ✅ Replaced script includes
- ✅ Removed `FingerprintManager.initialize()` and related functions
- ✅ Added fingerprint icon display (index and middle fingers)
- ✅ Added reader selection dropdown
- ✅ Updated to use `beginCapture()` and `serverEnroll()` from `custom.js`
- ✅ Added error checking for SDK loading
- ✅ Updated enrollment instructions

### 2. `admin/content_enroll_staff_fingerprint.php`
- ✅ Already had correct script includes
- ✅ Added error checking for SDK loading
- ✅ Enhanced error messages with `updateDeviceStatus()`

## How It Works Now

### Student Enrollment Flow:
1. Admin selects a student from dropdown
2. Page auto-detects fingerprint readers and populates dropdown
3. Admin selects a reader from dropdown
4. Admin clicks "Capture Fingerprint"
5. System captures 2 index finger scans, then 2 middle finger scans
6. Admin clicks "Enroll Fingerprint" to save

### Staff/Tutor Enrollment Flow:
1. Admin selects user type (Staff or Tutor)
2. Admin selects a user from dropdown
3. Page auto-detects fingerprint readers
4. Admin selects a reader from dropdown
5. Admin clicks "Capture Fingerprint"
6. System captures 2 index finger scans, then 2 middle finger scans
7. Admin clicks "Enroll Fingerprint" to save

## Key Functions Used from `custom.js`

### `beginEnrollment()`
- Initializes the enrollment system
- Populates the reader selection dropdown
- Sets up status field

### `beginCapture()`
- Validates that user ID and reader are selected
- Starts fingerprint capture
- Shows visual indicators for which finger to scan

### `serverEnroll()`
- For staff/tutor: Sends to `core/enroll_staff.php`
- For students: Overridden to send to the page's own AJAX handler
- Saves fingerprint template to database

### `clearCapture()`
- Clears all captured samples
- Resets UI indicators
- Allows starting over

## Testing Checklist

- [ ] Student enrollment page loads without errors
- [ ] Staff enrollment page loads without errors
- [ ] Reader dropdown populates with available devices
- [ ] Fingerprint capture starts when "Capture Fingerprint" is clicked
- [ ] Visual indicators show which finger to scan
- [ ] All 4 scans (2 index + 2 middle) complete successfully
- [ ] "Enroll Fingerprint" saves template to database
- [ ] Success message appears after enrollment
- [ ] Page reloads showing updated enrollment status

## Troubleshooting

### Issue: "Fingerprint SDK not loaded"
**Solution**: 
- Check that `js/fingerprint.sdk.min.js` exists
- Check browser console for script loading errors
- Verify all SDK files are in the `js/` directory

### Issue: "No devices detected"
**Solution**:
- Ensure DigitalPersona reader is connected via USB
- Check that DigitalPersona WebSDK service is running
- Try refreshing the page
- Check Windows Device Manager for reader

### Issue: Capture doesn't start
**Solution**:
- Ensure a user/student is selected
- Ensure a reader is selected from dropdown
- Check browser console for JavaScript errors
- Verify `custom.js` is loaded correctly

## Related Files

- `js/custom.js` - Contains all fingerprint functions
- `index.php` - Login page (working reference implementation)
- `core/enroll.php` - Student enrollment backend
- `core/enroll_staff.php` - Staff/tutor enrollment backend
- `admin/content_enroll_student_fingerprint.php` - Student enrollment UI
- `admin/content_enroll_staff_fingerprint.php` - Staff/tutor enrollment UI

## Notes

- Both enrollment pages now use the same underlying SDK integration as the login page
- The enrollment process captures 4 total scans (2 index + 2 middle) for better accuracy
- Templates are stored in JSON format in the `fingerprint_templates` table
- All enrollments are logged in the `biometric_logs` table

