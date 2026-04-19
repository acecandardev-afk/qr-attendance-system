# New machine setup (Windows)

Prerequisites: PHP 8.2 or newer, Composer, and Node.js 18 or newer on the PATH. For MySQL, start the database service and create an empty database before migrating. SQLite does not require a separate database server.

Open PowerShell or Command Prompt and change to the project directory. Example:

```bat
cd "C:\xampp\htdocs\smart attendance system\qr-attendance-system"
```

Install PHP dependencies:

```bat
composer install
```

If the project folder does not already contain a `.env` file, create it from the example:

```bat
copy .env.example .env
```

Generate the application key:

```bat
php artisan key:generate
```

Database configuration

Option A: SQLite (default in `.env.example`). Create the database file if needed, then run migrations and seeders:

```bat
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate
php artisan db:seed
```

Option B: MySQL (for example with XAMPP). Create an empty database, then edit `.env` so `DB_CONNECTION=mysql` and set `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` to match your server. Run:

```bat
php artisan migrate
php artisan db:seed
```

Install Node dependencies and build front-end assets for production:

```bat
npm install
npm run build
```

Start the application using either:

```bat
php artisan serve
```

or:

```bat
php -S 127.0.0.1:8000 -t public
``` 

In the browser, open `http://127.0.0.1:8000`, or the URL shown in the terminal if you use a different host or port.

After seeding, default accounts use the password `password`. Sample addresses and HTTPS notes for phone cameras are documented in `README.md`.

## Daily start (recommended)

From the project folder, double‑click **`smart attendance start.bat`** (or run it from Explorer). It starts Laravel on port **8000**, starts **Caddy** HTTPS on **9443** when available, and opens your browser.

- **PC:** `http://127.0.0.1:8000`
- **Phone (same Wi‑Fi):** `https://<your-PC-LAN-IPv4>:9443` — not `localhost` on the phone (see `README.md`).

Optional arguments (passed through to the launcher):

- `smart attendance start.bat -SkipBootstrap` — faster restart (skip composer/migrate/npm checks)
- `smart attendance start.bat -SkipCaddy` — HTTP only (no HTTPS; phone camera usually needs HTTPS)
- `smart attendance start.bat -NoBrowser` — do not auto-open a browser window
