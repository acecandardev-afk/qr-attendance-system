# Smart Attendance System (QR-Based)

QR-based attendance for **Negros Oriental State University (NORSU) – Guihulngan Campus**.

## Features

- Sign in with email and password; **forgot password** (email reset link; configure mail in `.env`)
- Roles: **admin**, **faculty**, **student**
- Admin: users, departments, courses, sections, schedules, enrollments, reports, attendance settings, security logs
- Faculty: QR attendance sessions (scheduled + ad-hoc), manual attendance, class reports
- Student: QR scan (with offline queue + sync when back online), attendance history
- PWA-style shell: `manifest.webmanifest` + service worker

## Requirements

- PHP 8.2+, Composer, Node.js 18+ (for front-end build)
- MySQL (typical with XAMPP) or SQLite for local dev

## Setup

1. **Dependencies**

   ```bash
   composer install
   npm install
   ```

2. **Environment**

   - Copy `.env.example` to `.env` and run `php artisan key:generate`.
   - For **MySQL** (XAMPP): set `DB_CONNECTION=mysql`, create database `qr_attendance_db`, and set `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`.
   - For **password reset emails**: set `MAIL_*` in `.env` (e.g. SMTP). With `MAIL_MAILER=log`, messages are written to the log only.

3. **Database**

   ```bash
   php artisan migrate
   php artisan db:seed
   ```

4. **Front-end assets**

   - **Production / typical XAMPP:** build once (outputs to `public/build`):

     ```bash
     npm run build
     ```

   - **Local development with hot reload:** `npm run dev` (keep it running). Laravel will use the Vite dev server when `public/hot` exists.

## How to run

### Option A — PHP built-in server (good with XAMPP PHP)

```bash
cd "path/to/qr-attendance-system"
php -S 127.0.0.1:8000 -t public
```

Open `http://127.0.0.1:8000`.

### Option B — Apache (XAMPP)

Point the virtual host or alias **document root** to the project’s `public` folder, then open your configured URL.

### Note on `php artisan serve`

This project includes a root **`server.php`** router so `php artisan serve` works even when the copy inside `vendor/laravel/framework` is missing. If anything still fails, use **Option A** or Apache.

## Sample credentials (after `db:seed`)

From `database/seeders/UserSeeder.php` (all seeded users use password **`password`** unless you change the seeder):

- **Admin:** `admin@school.edu`
- **Faculty:** `jdoe@school.edu`, `msmith@school.edu`, `rjohnson@school.edu`
- **Students:** `student1@school.edu`, `student2@school.edu`, etc.

## Offline scanning (students)

- **Online:** scans post to the server immediately.
- **Offline:** scans are stored in the browser and submitted when the connection returns.

## Troubleshooting

| Issue | What to do |
|--------|------------|
| Blank styles / 404 on `/build/...` | Run `npm run build`. Remove `public/hot` if you are not running `npm run dev`. |
| Database errors | Start MySQL; verify `.env` `DB_*` values. |
| Reset email never arrives | Configure real `MAIL_*` in `.env`; check spam and logs. |

## License

MIT
