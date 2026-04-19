# Smart Attendance System (QR-Based)

QR-based attendance for **Negros Oriental State University (NORSU) – Guihulngan Campus**.

**Repository:** [github.com/acecandardev-afk/qr-attendance-system](https://github.com/acecandardev-afk/qr-attendance-system)

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

## Deploy online (GitHub → Vercel)

The project ships with **`vercel.json`** and **`api/index.php`** so Laravel can run on [Vercel](https://vercel.com) as a PHP serverless app, with static assets from `public/`.

1. Push this repo to GitHub (or use the existing remote):  
   [https://github.com/acecandardev-afk/qr-attendance-system](https://github.com/acecandardev-afk/qr-attendance-system)
2. In Vercel: **Add New → Project → Import** that repository. Vercel will use the root **`vercel.json`** build and routes.
3. **Environment variables** (Production) — set at least:

   | Variable | Value / notes |
   |----------|----------------|
   | `APP_KEY` | Generate locally: `php artisan key:generate --show` and paste the `base64:…` value. |
   | `APP_ENV` | `production` |
   | `APP_DEBUG` | `false` |
   | `APP_URL` | Your deployment URL, e.g. `https://your-app.vercel.app` |
   | `TRUSTED_PROXIES` | `*` (required so HTTPS, sessions, and redirects work behind Vercel’s edge) |
   | Database | Use a **hosted** database (Vercel Postgres, Neon, PlanetScale, remote MySQL, etc.). **Do not rely on SQLite** in the repo for production on Vercel — the filesystem is not suitable for a durable SQLite file. Set `DB_*` or `DATABASE_URL` / `DB_URL` accordingly. |

4. **First-time database:** point your local `.env` at the same production `DATABASE_URL` (or run from CI), then:

   ```bash
   php artisan migrate --force
   php artisan db:seed   # optional
   ```

5. Redeploy if needed. Open your `APP_URL` in the browser.

**Docker:** the included **`Dockerfile`** + **`docker-entrypoint.sh`** can be used on Railway, Render, Fly.io, or any host that runs containers (set `PORT`, database env vars, and run migrations once).

**Scheduler note:** `bootstrap/app.php` registers a **minute** schedule (e.g. auto-closing attendance sessions). Serverless hosts do not run `php artisan schedule:work`. For production, add an external cron or your platform’s cron feature that calls `schedule:run` (or equivalent) if you rely on that behavior.

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

### Phones and QR scanning (HTTPS)

Student **camera access** only works in a **secure context**: **HTTPS** or **localhost**. Opening `http://192.168.x.x:8000` on a phone will show a security error and the scanner will not start.

**Air-gapped / no internet (same WiFi only):** use **local HTTPS** on your LAN. The app does **not** need the public internet for attendance; phones only need to reach your server on the local network.

1. Install **Caddy**. On Windows: `winget install -e --id CaddyServer.Caddy`, then **close and reopen** the terminal (or run `powershell -ExecutionPolicy Bypass -File scripts/run-caddy.ps1`, which refreshes PATH). Alternatively: [Caddy install docs](https://caddyserver.com/docs/install), or configure **Apache SSL** / **nginx** with a certificate for your LAN IP.
2. Run Laravel on the server PC, bound to loopback (Caddy terminates TLS and proxies here):

   ```bash
   php artisan serve --host=127.0.0.1 --port=8000
   ```

3. From this repo: `caddy run --config Caddyfile` (listens on **9443** with `tls internal`; see `Caddyfile`).
4. On each phone, open `https://YOUR_SERVER_LAN_IP:9443`. Accept the certificate warning the first time (or install Caddy’s local root CA on devices for a “green lock”).
5. Set `.env`: `APP_URL=https://YOUR_SERVER_LAN_IP:9443`, then `php artisan config:clear`. Leave **`SESSION_SECURE_COOKIE` unset** if you also use **`http://127.0.0.1:8000`** on the PC (otherwise login can return **419**); the app sets the cookie’s Secure flag per request automatically.

The QR scanner script is served from **`public/vendor/html5-qrcode.min.js`** (no CDN required). Layout fonts may fall back to system fonts if Google Fonts cannot load.

**Optional — Cloudflare Tunnel (requires internet):** if the PC and phones **do** have outbound internet, `cloudflared tunnel --url http://127.0.0.1:8000` gives an HTTPS URL without opening firewall ports. This **does not** work on a fully offline network.

For HTTP-only LAN testing (no camera), use `composer run serve:lan` or `php artisan serve --host=0.0.0.0 --port=8000`.

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
| Phone says camera needs HTTPS / localhost | Use **HTTPS** on your LAN (e.g. **Caddy** / Apache SSL). Offline sites cannot use Cloudflare Tunnel. Plain `http://192.168…` cannot use the camera. |
| Windows `curl` → `SEC_E_INTERNAL_ERROR` / schannel | Common **offline**: revocation check can’t reach the internet. Use `curl.exe -vk --ssl-no-revoke https://YOUR_IP:9443`. To trust Caddy’s CA on the PC: **Administrator** terminal → `caddy trust`, then retry. Phones usually still open the site after accepting the cert warning. |
| Blank styles / 404 on `/build/...` | Run `npm run build`. Remove `public/hot` if you are not running `npm run dev`. |
| Database errors | Start MySQL; verify `.env` `DB_*` values. |
| Reset email never arrives | Configure real `MAIL_*` in `.env`; check spam and logs. |

## License

MIT
