@echo off
title Laravel - localhost only
cd /d "%~dp0"

echo.
echo  http://127.0.0.1:8000  (this PC only - not reachable from other devices)
echo  No class server. For offline Wi-Fi + phone cameras: "smart attendance start.bat". For Cloudflare: "easy phone HTTPS.bat".
echo.
echo  Press Ctrl+C to stop the server.
echo.

php artisan serve --host=127.0.0.1 --port=8000

pause
