# How to start the system (manual reference)

All paths below assume the project root is:

`C:\xampp\htdocs\qr-attendance-system`  
(Adjust if yours differs.)

---

## Prerequisites (one-time)

- **PHP** on PATH, **Composer** dependencies installed (`composer install`).
- **`.env`** present with `APP_KEY`, database settings, and `php artisan migrate` already run.
- **Front-end built** if `public/build/manifest.json` is missing: `npm install` then `npm run build`.
- **Caddy** (for HTTPS on phones / camera): `winget install -e --id CaddyServer.Caddy`  
- **cloudflared** (optional, internet only): `winget install -e --id Cloudflare.cloudflared`

---

## Recommended: one launcher (offline, same Wi‑Fi)

Double‑click:

**`smart attendance start.bat`**

It will:

- Start **Laravel** on all interfaces: `http://0.0.0.0:8000`
- Start **Caddy** with the project **`Caddyfile`**: **`https://…:9443`** → proxies to `http://127.0.0.1:8000`
- Try to add **Windows Firewall** rules for TCP **8000** and **9443** (approve UAC if prompted)

**On this PC (browser):** `http://127.0.0.1:8000`  
**On phones (same Wi‑Fi, camera / QR):** `https://<PC-LAN-IPv4>:9443`  
(Get `<PC-LAN-IPv4>` from `ipconfig` → your Wi‑Fi adapter → IPv4. Accept the certificate warning the first time.)

---

## Fully manual (two terminals)

Open a terminal **in the project folder**.

### Terminal 1 — Laravel

```bat
cd /d C:\xampp\htdocs\qr-attendance-system
php artisan serve --host=0.0.0.0 --port=8000
```

Leave it running.

### Terminal 2 — Caddy (HTTPS for phones)

```bat
cd /d C:\xampp\htdocs\qr-attendance-system
caddy run --config Caddyfile
```

Leave it running.

Same URLs as above: PC → `http://127.0.0.1:8000`, phones → `https://<LAN-IP>:9443`.

---

## This PC only (no class / no phones)

```bat
cd /d C:\xampp\htdocs\qr-attendance-system
php artisan serve --host=127.0.0.1 --port=8000
```

Or double‑click **`localhost only.bat`**.  
Not reachable from other devices.

---

## Internet + Cloudflare (optional)

If you have internet and want a single **`https://….trycloudflare.com`** link (valid certificate, simpler for phones):

**`easy phone HTTPS.bat`** or **`public class.bat`**

Still needs Laravel on port **8000** (the script starts it if missing).  
Set **`APP_URL`** in **`.env`** to that tunnel URL if login/CSRF misbehave, then:

```bat
php artisan config:clear
```

---

## Firewall

If devices cannot connect:

- Allow **inbound TCP 8000** and **9443** on **Private** networks, **or**
- Run **`smart attendance start.bat`** as **Administrator** once so it can create rules, **or**
- See Windows help for firewall (avoid turning the whole firewall off unless you understand the risk).

---

## Quick reference

| Goal | What to run |
|------|------------------|
| Offline class + phone camera | **`smart attendance start.bat`** → phones use **`https://LAN-IP:9443`** |
| Manual Laravel + Caddy | `php artisan serve --host=0.0.0.0 --port=8000` + `caddy run --config Caddyfile` |
| This PC only | **`localhost only.bat`** or `php artisan serve --host=127.0.0.1 --port=8000` |
| Cloudflare tunnel | **`easy phone HTTPS.bat`** |

---

## Stop the system

- Close the **Laravel** terminal (or Ctrl+C).
- Close the **Caddy** window (or Ctrl+C).
- For Cloudflare, Ctrl+C in the tunnel window.
