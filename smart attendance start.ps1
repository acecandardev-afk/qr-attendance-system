# Offline LAN launcher: Laravel + Caddy (HTTPS for phone cameras on same Wi-Fi). See scripts\start-dev-stack.ps1
# Internet + Cloudflare quick tunnel: use "easy phone HTTPS.bat" or "public class.bat"

param(
    [switch]$NoBrowser,
    [switch]$InstallShortcut,
    [switch]$SkipBootstrap,
    [switch]$SkipCaddy,
    [switch]$SkipFirewallElevation
)

$ErrorActionPreference = "Stop"
$script = Join-Path $PSScriptRoot "scripts\start-dev-stack.ps1"
if (-not (Test-Path -LiteralPath $script)) {
    Write-Host "Missing: $script" -ForegroundColor Red
    exit 1
}
try {
    & $script -NoBrowser:$NoBrowser -InstallShortcut:$InstallShortcut -SkipBootstrap:$SkipBootstrap -SkipCaddy:$SkipCaddy -SkipFirewallElevation:$SkipFirewallElevation
} catch {
    Write-Host $_.Exception.Message -ForegroundColor Red
    exit 1
}
