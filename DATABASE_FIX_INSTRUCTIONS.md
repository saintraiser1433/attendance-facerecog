# Database Configuration Fix

## Problem
The system was trying to connect to database `seaitfinger` but your actual database is `testdb`. Also, the `biometric_logs` table doesn't exist.

## Fixes Applied

### 1. Updated Database Names

**File: `core/Database.php`**
- Changed from: `"seaitfinger"`
- Changed to: `"testdb"`

**File: `db_conn.php`**
- Changed from: `"cuteko"`
- Changed to: `"testdb"`

### 2. Fixed Parameter Types

**File: `core/querydb.php`**
- Fixed `logBiometricAction()` function parameter types
- Changed from: `"sssdsss"` (incorrect)
- Changed to: `"issdiss"` (correct)

Parameter order:
1. `user_id` - int (i)
2. `user_type` - string (s)
3. `action_type` - string (s)
4. `match_score` - double (d)
5. `success` - int (i)
6. `ip_address` - string (s)
7. `device_info` - string (s)

## Next Steps

### Create the `biometric_logs` Table

Run this SQL in your `testdb` database:

```sql
CREATE TABLE IF NOT EXISTS `biometric_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `user_type` enum('student','staff','tutor') NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `fingerprint_match_score` decimal(5,2) DEFAULT NULL,
  `success` tinyint(1) NOT NULL DEFAULT '0',
  `ip_address` varchar(45) DEFAULT NULL,
  `device_info` text,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`,`user_type`),
  KEY `idx_timestamp` (`timestamp` DESC),
  KEY `idx_action` (`action_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```

Or use the SQL file I created: `db/create_biometric_logs.sql`

### How to Run the SQL

1. Open phpMyAdmin
2. Select your `testdb` database
3. Click on "SQL" tab
4. Copy and paste the SQL above (or open `db/create_biometric_logs.sql`)
5. Click "Go" to execute

## Verification

After creating the table, test the fingerprint verification again. The error should be gone and logs should be recorded in the `biometric_logs` table.

## Files Modified

1. ✅ `core/Database.php` - Updated database name to `testdb`
2. ✅ `db_conn.php` - Updated database name to `testdb`
3. ✅ `core/querydb.php` - Fixed parameter types for `logBiometricAction()`
4. ✅ `db/create_biometric_logs.sql` - Created SQL file for table creation

