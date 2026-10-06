# Org. Digital Attendance System — PHP + SQLite

A ready-to-run QR attendance system based on the system specification in the provided screenshot.

## Included modules

1. Dashboard (Read)
2. Information Management — Members CRUD
3. Reports
4. User Management — Admin CRUD
5. Activity Logs / Audit Trail
6. Data validation and authentication
7. Navigation + responsive UI
8. Auto-generated QR code per member
9. Camera QR scanner
10. Automatic IN/OUT attendance logic
11. SQLite database — no MySQL setup required
12. CSRF protection and password hashing

## Requirements

- PHP 8.0+
- PDO SQLite extension enabled
- XAMPP, Laragon, WAMP, or PHP built-in server
- Camera permission for QR scanning
- Internet connection in the browser for the QRCode.js and html5-qrcode CDN scripts

## XAMPP setup

1. Extract this folder into:
   `C:\xampp\htdocs\org_digital_attendance`
2. Start Apache in XAMPP.
3. Open:
   `http://localhost/org_digital_attendance/`
4. Login:
   Username: `admin`
   Password: `admin123`

The SQLite database is created automatically in `database/attendance.sqlite`.

## First use

1. Login.
2. Open Members / QR.
3. Add a member with a unique Member Code.
4. Click `QR` to display that member's QR code.
5. Open Scan QR.
6. Allow camera access.
7. Scan the generated QR.
8. The first scan of the day is IN, the next scan is OUT, alternating thereafter.
9. Open Reports to see attendance.
10. Open Logs to see the audit trail.

## Important production changes

- Change the default admin password immediately.
- Use HTTPS when deployed online.
- Restrict access to the database folder.
- Back up `database/attendance.sqlite`.
- For a large organization, consider MySQL/PostgreSQL and server-side rate limiting.
- Do not expose admin credentials publicly.

## QR format

Each generated QR contains JSON similar to:

{
  "type": "ORG_ATTENDANCE",
  "member_id": 1,
  "member_code": "ORG-001",
  "token": "random-secret-token"
}

The scanner sends the token to `api/scan.php`, which validates the member server-side before recording attendance.
