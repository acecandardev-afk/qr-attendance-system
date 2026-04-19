# Run Caddy with the project Caddyfile (HTTPS :9443 proxies to Laravel :8000).
# If "caddy" is not recognized, either:
#   - Close and reopen PowerShell, or
#   - winget install -e --id CaddyServer.Caddy

$ErrorActionPreference = "Stop"
$root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
Set-Location $root

# Pick up PATH from a fresh winget install without restarting the shell
$env:Path = [System.Environment]::GetEnvironmentVariable("Path", "Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path", "User")

# winget often installs here but the current session was started before PATH was updated
$caddyDirs = @(
    (Join-Path $env:ProgramFiles "Caddy"),
    (Join-Path ([Environment]::GetFolderPath('ProgramFilesX86')) "Caddy")
)
foreach ($dir in $caddyDirs) {
    $exe = Join-Path $dir "caddy.exe"
    if (Test-Path -LiteralPath $exe) {
        $env:Path = "$dir;$env:Path"
        break
    }
}

if (-not (Get-Command caddy -ErrorAction SilentlyContinue)) {
    Write-Host "Caddy is not installed or not on PATH." -ForegroundColor Yellow
    Write-Host "Install: winget install -e --id CaddyServer.Caddy" -ForegroundColor Cyan
    Write-Host "Then close this window, open a new PowerShell, and run this script again." -ForegroundColor Yellow
    exit 1
}

if (-not $env:CADDY_LAN_IP -or $env:CADDY_LAN_IP.Trim() -eq "") {
    $detected = Get-NetIPAddress -AddressFamily IPv4 -ErrorAction SilentlyContinue |
        Where-Object {
            $_.IPAddress -notmatch '^127\.' -and
            $_.IPAddress -notmatch '^169\.254\.' -and
            $_.PrefixOrigin -ne 'WellKnown'
        } |
        Sort-Object InterfaceMetric
    $env:CADDY_LAN_IP = ($detected | Select-Object -First 1).IPAddress
}

if (-not $env:CADDY_LAN_IP -or $env:CADDY_LAN_IP.Trim() -eq "") {
    Write-Host "Could not detect LAN IPv4 (optional - Caddy listens on :9443 on all interfaces)." -ForegroundColor DarkYellow
    Write-Host "Set DEV_LAN_IP in .env or: `$env:CADDY_LAN_IP = '192.168.x.x' so scripts show the right URL." -ForegroundColor DarkYellow
    $env:CADDY_LAN_IP = "127.0.0.1"
}

Write-Host "CADDY_LAN_IP=$($env:CADDY_LAN_IP) (for display only - override if wrong)" -ForegroundColor Green
Write-Host "HTTPS URL: https://$($env:CADDY_LAN_IP):9443 -> http://127.0.0.1:8000 (Laravel: php artisan serve on :8000)" -ForegroundColor Green
& caddy run --config Caddyfile
