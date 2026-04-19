# Publishes your local Laravel app over HTTPS so phones can use the QR camera.
# Requires INTERNET on the PC and on phones — not for air-gapped sites (use Caddyfile instead).
# Prerequisites: PHP; Cloudflare Tunnel ("cloudflared") in PATH:
#   https://developers.cloudflare.com/cloudflare-one/connections/connect-apps/install-and-setup/installation/
#
# Usage (from project root):
#   1. Terminal A: php artisan serve --host=127.0.0.1 --port=8000
#   2. Terminal B: powershell -ExecutionPolicy Bypass -File scripts/cloudflared-tunnel.ps1
#
# Copy the printed https://....trycloudflare.com URL into .env as APP_URL=...
# Set SESSION_SECURE_COOKIE=true, then run: php artisan config:clear

$ErrorActionPreference = "Stop"
$root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
Set-Location $root

if (-not (Get-Command cloudflared -ErrorAction SilentlyContinue)) {
    Write-Host "cloudflared is not in PATH. Install it from:" -ForegroundColor Yellow
    Write-Host "  https://developers.cloudflare.com/cloudflare-one/connections/connect-apps/install-and-setup/installation/" -ForegroundColor Cyan
    exit 1
}

Write-Host "Tunneling to http://127.0.0.1:8000 — ensure Laravel is running there (php artisan serve --host=127.0.0.1 --port=8000)" -ForegroundColor Green
cloudflared tunnel --url http://127.0.0.1:8000
