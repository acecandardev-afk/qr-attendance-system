# Manual (Quick Guide)

## 1) How to run the system

1. Stop any running servers.
2. Open PowerShell in the project folder:

```powershell
cd "c:\xampp\htdocs\smart attendance system\qr-attendance-system"
php -S 127.0.0.1:8000 -t public
```php -S 127.0.0.1:8000 -t public

3. Open the browser:

- `http://127.0.0.1:8000/`

> Do NOT use `php artisan serve` (it may fail on this setup).  

## 2) Roles

- **Admin**: manages users, departments, courses, sections, schedules, enrollments; views reports and security logs; updates attendance settings.
- **Faculty**: starts QR attendance sessions, shows QR to students, and can mark attendance manually (present/late/excused/absent) including bulk actions.
- **Student**: scans QR to mark attendance and can view history.

## 3) Typical workflow

1. **Admin**
   - Create users for faculty and students.
   - Create departments, courses, sections.
   - Create schedules (link course + section + faculty + day + time).
   - Enroll students into sections.
2. **Faculty**
   - Go to **Sessions**.
   - Start attendance for a schedule.
   - Display the QR until the session expires/closed.
   - If needed, use **Manual Attendance** (single or bulk).
3. **Student**
   - Go to **Mark Attendance** → **Scan QR Code**.
   - Scan the QR and submit.
   - If offline, scans are queued and synced when back online.

## 4) Common UI actions

- **Start Attendance**: creates a session and generates the QR.
- **Close Session**: marks the session as closed.
- **Manual Attendance (Faculty)**:
  - Change each student status (Present/Late/Excused/Absent).
  - Use bulk actions to apply status to all/unmarked students.

## 5) Offline scanning (students)

- If the device is offline, the scan is stored locally in the browser.
- When internet returns, the app automatically sends queued scans to the server.

## 6) Common errors

### `127.0.0.1 refused to connect`

- The server is not running or you used the wrong URL/port.
- Start the server with the command in **Section 1**, then use `http://127.0.0.1:8000`.

### `Failed opening required .../resources/server.php`

- This happens when using `php artisan serve`.
- Fix: use `php -S 127.0.0.1:8000 -t public` instead.

