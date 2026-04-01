# Fingerprint Data Flow Diagram

## Enrollment Process - Complete Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                    USER SELECTS PERSON TO ENROLL                 │
│                  (Student/Staff/Tutor from Admin Panel)          │
└────────────────────────────────┬────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────┐
│               DIGITALPERSONA FINGERPRINT READER                  │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  1. User places INDEX FINGER → Capture Sample 1         │   │
│  │  2. User places INDEX FINGER → Capture Sample 2         │   │
│  │  3. User places MIDDLE FINGER → Capture Sample 3        │   │
│  │  4. User places MIDDLE FINGER → Capture Sample 4        │   │
│  └─────────────────────────────────────────────────────────┘   │
└────────────────────────────────┬────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────┐
│                    JAVASCRIPT (custom.js)                        │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  Hand Object Created:                                    │   │
│  │  {                                                       │   │
│  │    id: user_id,                                         │   │
│  │    index_finger: [sample1, sample2],                    │   │
│  │    middle_finger: [sample3, sample4]                    │   │
│  │  }                                                       │   │
│  └─────────────────────────────────────────────────────────┘   │
└────────────────────────────────┬────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────┐
│              AJAX POST to core/enroll.php                        │
│                    or core/enroll_staff.php                      │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  POST Data:                                              │   │
│  │  {                                                       │   │
│  │    user_id: 5,                                          │   │
│  │    user_type: "staff",                                  │   │
│  │    index_finger: [sample1, sample2],                    │   │
│  │    middle_finger: [sample3, sample4]                    │   │
│  │  }                                                       │   │
│  └─────────────────────────────────────────────────────────┘   │
└────────────────────────────────┬────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────┐
│            PHP: enroll_fingerprint() Function                    │
│              (From helpers/helpers.php)                          │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  Process 2 index samples → 1 index template             │   │
│  │  Process 2 middle samples → 1 middle template           │   │
│  │                                                          │   │
│  │  Returns:                                                │   │
│  │  {                                                       │   │
│  │    enrolled_index_finger: "BASE64_TEMPLATE_DATA...",    │   │
│  │    enrolled_middle_finger: "BASE64_TEMPLATE_DATA..."    │   │
│  │  }                                                       │   │
│  └─────────────────────────────────────────────────────────┘   │
└────────────────────────────────┬────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────┐
│           PHP: setFingerprintTemplate() Function                 │
│                  (From querydb.php)                              │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  Creates JSON structure:                                 │   │
│  │  {                                                       │   │
│  │    "index_finger": "ENROLLED_INDEX_TEMPLATE",           │   │
│  │    "middle_finger": "ENROLLED_MIDDLE_TEMPLATE",         │   │
│  │    "enrolled_at": "2025-11-05 12:30:45"                 │   │
│  │  }                                                       │   │
│  └─────────────────────────────────────────────────────────┘   │
└────────────────────────────────┬────────────────────────────────┘
                                 │
                    ┌────────────┴────────────┐
                    ▼                         ▼
    ┌───────────────────────────┐  ┌──────────────────────────┐
    │  Check if User Exists     │  │  Log Enrollment Activity │
    │  in fingerprint_templates │  │  in biometric_logs       │
    └───────────┬───────────────┘  └──────────────────────────┘
                │
        ┌───────┴────────┐
        ▼                ▼
    ┌────────┐      ┌─────────┐
    │ UPDATE │      │ INSERT  │
    └────┬───┘      └────┬────┘
         │               │
         └───────┬───────┘
                 ▼
┌─────────────────────────────────────────────────────────────────┐
│                    DATABASE TABLES                               │
│                                                                  │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  fingerprint_templates                                   │  │
│  ├────┬──────┬───────────┬────────────────────────────────┐ │  │
│  │ id │user_id│user_type │ fingerprint_template           │ │  │
│  ├────┼──────┼───────────┼────────────────────────────────┤ │  │
│  │ 1  │  5   │  staff    │ {"index_finger":"...","midd...│ │  │
│  └────┴──────┴───────────┴────────────────────────────────┘ │  │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  biometric_logs                                          │  │
│  ├────┬──────┬───────────┬─────────────┬─────────┬────────┐ │  │
│  │ id │user_id│user_type │ action_type │ success │timestamp│ │  │
│  ├────┼──────┼───────────┼─────────────┼─────────┼────────┤ │  │
│  │ 1  │  5   │  staff    │ Enrollment  │    1    │ 2025...│ │  │
│  └────┴──────┴───────────┴─────────────┴─────────┴────────┘ │  │
└─────────────────────────────────────────────────────────────────┘
```

## Verification Process - Complete Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                  USER WANTS TO LOGIN/CHECK-IN                    │
│                    (index.php - Login Page)                      │
└────────────────────────────────┬────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────┐
│          SELECT FINGERPRINT READER (verifyReaderSelect)          │
└────────────────────────────────┬────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────┐
│              CLICK "TIME IN" or "TIME OUT" BUTTON                │
│              Calls: captureForIdentifyIn()                       │
└────────────────────────────────┬────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────┐
│            DIGITALPERSONA CAPTURES FINGERPRINT                   │
│              User places finger → Capture 2 samples              │
└────────────────────────────────┬────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────┐
│                  JAVASCRIPT (custom.js)                          │
│            serverIdentify() function sends data                  │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  Data sent:                                              │   │
│  │  {                                                       │   │
│  │    id: null,  // No ID, we're identifying              │   │
│  │    index_finger: [captured_sample],                     │   │
│  │    middle_finger: [],                                   │   │
│  │    sched: schedule_id,                                  │   │
│  │    type: "IN" or "OUT"                                  │   │
│  │  }                                                       │   │
│  └─────────────────────────────────────────────────────────┘   │
└────────────────────────────────┬────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────┐
│                AJAX POST to core/verify.php                      │
└────────────────────────────────┬────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────┐
│        RETRIEVE ALL ENROLLED FINGERPRINTS FROM DATABASE          │
│                getAllByUserType('student')                       │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  Query fingerprint_templates table                       │   │
│  │  Get all records where user_type = 'student'            │   │
│  │                                                          │   │
│  │  Returns array of:                                       │   │
│  │  [                                                       │   │
│  │    {                                                     │   │
│  │      user_id: 4,                                        │   │
│  │      index_finger: "TEMPLATE_DATA...",                  │   │
│  │      middle_finger: "TEMPLATE_DATA..."                  │   │
│  │    },                                                    │   │
│  │    { ... },                                             │   │
│  │    { ... }                                              │   │
│  │  ]                                                       │   │
│  └─────────────────────────────────────────────────────────┘   │
└────────────────────────────────┬────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────┐
│              COMPARE CAPTURED VS ENROLLED                        │
│              verify_fingerprint() Function                       │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  Loop through each enrolled user:                        │   │
│  │                                                          │   │
│  │  FOR EACH enrolled_user:                                 │   │
│  │    Compare captured_sample with enrolled_user.index     │   │
│  │    Compare captured_sample with enrolled_user.middle    │   │
│  │                                                          │   │
│  │    IF match_score > threshold:                          │   │
│  │      RETURN "match"                                     │   │
│  │      BREAK loop                                         │   │
│  └─────────────────────────────────────────────────────────┘   │
└────────────────────────────────┬────────────────────────────────┘
                                 │
                    ┌────────────┴────────────┐
                    ▼                         ▼
          ┌──────────────┐          ┌──────────────────┐
          │ MATCH FOUND  │          │  NO MATCH FOUND  │
          └──────┬───────┘          └────────┬─────────┘
                 │                           │
                 ▼                           ▼
     ┌───────────────────────┐   ┌──────────────────────────┐
     │ Log Success           │   │ Log Failed Verification  │
     │ biometric_logs        │   │ biometric_logs           │
     │ action='Check-In'     │   │ action='Verification     │
     │ success=1             │   │         Failed'          │
     │ score=95.0            │   │ success=0                │
     └───────┬───────────────┘   └──────────┬───────────────┘
             │                              │
             ▼                              ▼
 ┌────────────────────────┐    ┌────────────────────────────┐
 │ Record Attendance      │    │ Show Error Message         │
 │ insertAttendance.php   │    │ "No match found"           │
 │                        │    └────────────────────────────┘
 │ student_attendance or  │
 │ staff_attendance or    │
 │ tutor_attendance       │
 │                        │
 │ Fields:                │
 │ - student_id           │
 │ - attendance_date      │
 │ - check_in_time        │
 │ - status: 'Present'    │
 │ - is_biometric: 1      │
 │ - match_score: 95.0    │
 └────────┬───────────────┘
          │
          ▼
 ┌────────────────────────┐
 │ Display Success        │
 │ - Show user name       │
 │ - Show user photo      │
 │ - Show attendance time │
 │ - Show match score     │
 └────────────────────────┘
```

## Database Storage Format

### How 4 Fingerprint Samples Become 1 Database Record

```
┌──────────────────────────────────────────────────────────────┐
│              CAPTURE PHASE (DigitalPersona)                   │
├──────────────────────────────────────────────────────────────┤
│                                                               │
│  Index Finger Sample 1: "Rk1SACAyMAAAAAEYAQ..." (512 bytes) │
│  Index Finger Sample 2: "Rk1SACAyMAAAAAEYAQ..." (512 bytes) │
│  Middle Finger Sample 1: "Rk1SACAyMAAAAAEYAQ..." (512 bytes)│
│  Middle Finger Sample 2: "Rk1SACAyMAAAAAEYAQ..." (512 bytes)│
│                                                               │
│  Total Raw Data: ~2KB                                        │
└──────────────────────────────────────────────────────────────┘
                            ↓
                            ↓  enroll_fingerprint()
                            ↓
┌──────────────────────────────────────────────────────────────┐
│              PROCESSING PHASE (PHP Backend)                   │
├──────────────────────────────────────────────────────────────┤
│                                                               │
│  Combine Sample 1 + Sample 2 → Index Template (256 bytes)   │
│  Combine Sample 3 + Sample 4 → Middle Template (256 bytes)  │
│                                                               │
│  Total Processed Data: ~512 bytes                            │
└──────────────────────────────────────────────────────────────┘
                            ↓
                            ↓  JSON.stringify()
                            ↓
┌──────────────────────────────────────────────────────────────┐
│              STORAGE PHASE (Database)                         │
├──────────────────────────────────────────────────────────────┤
│                                                               │
│  fingerprint_template (LONGTEXT):                            │
│  {                                                            │
│    "index_finger": "Rk1SACAyMAAAAAEYAQAA...",  (256 bytes) │
│    "middle_finger": "Rk1SACAyMAAAAAEYAQAA...", (256 bytes) │
│    "enrolled_at": "2025-11-05 12:30:45"                      │
│  }                                                            │
│                                                               │
│  Total Database Storage: ~550 bytes (including JSON)         │
└──────────────────────────────────────────────────────────────┘
```

## User Type Routing

```
                    ┌─────────────────┐
                    │  User Selects   │
                    │   User Type     │
                    └────────┬────────┘
                             │
         ┌───────────────────┼───────────────────┐
         │                   │                   │
         ▼                   ▼                   ▼
    ┌─────────┐        ┌─────────┐        ┌─────────┐
    │ STUDENT │        │  STAFF  │        │  TUTOR  │
    └────┬────┘        └────┬────┘        └────┬────┘
         │                   │                   │
         ▼                   ▼                   ▼
  ┌─────────────┐    ┌──────────────┐   ┌──────────────┐
  │ students    │    │ users        │   │ tutors       │
  │ table       │    │ table        │   │ table        │
  │ (id=4)      │    │ (id=5,       │   │ (id=3)       │
  │             │    │  role='user')│   │              │
  └──────┬──────┘    └──────┬───────┘   └──────┬───────┘
         │                   │                   │
         └───────────────────┼───────────────────┘
                             │
                             ▼
              ┌──────────────────────────────┐
              │  fingerprint_templates       │
              ├──────┬───────┬───────────────┤
              │user_id│ type  │   template   │
              ├──────┼───────┼───────────────┤
              │  4   │student│ {...}         │
              │  5   │staff  │ {...}         │
              │  3   │tutor  │ {...}         │
              └──────┴───────┴───────────────┘
```

## Summary

### Enrollment: 4 Samples → 1 Database Record
- **Capture**: 2 index + 2 middle finger samples
- **Process**: Combine into 2 templates (1 per finger type)
- **Store**: Single JSON object in `fingerprint_template` column
- **Log**: Record in `biometric_logs` table

### Verification: 1 Sample → Database Search → Match
- **Capture**: 1 finger sample
- **Retrieve**: All enrolled templates for user type
- **Compare**: Sample against each template
- **Match**: Return user info if threshold exceeded
- **Record**: Log result and mark attendance

