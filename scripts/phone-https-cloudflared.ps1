# Used by "easy phone HTTPS.bat" / "public class.bat". Offline LAN: use "smart attendance start.bat" (Caddy).
# SKIP_OPEN_TUNNEL_BROWSER=1 disables auto-open.

$ErrorActionPreference = "Stop"
$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

$env:Path = [System.Environment]::GetEnvironmentVariable("Path", "Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path", "User")

function Test-PortListening {
    param([int]$Port)
    $conn = Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue | Select-Object -First 1
    return $null -ne $conn
}

if (-not (Get-Command php -ErrorAction SilentlyContinue)) {
    Write-Host "PHP not on PATH." -ForegroundColor Red
    exit 1
}

if (-not (Test-PortListening 8000)) {
    $phpExe = (Get-Command php -ErrorAction Stop).Source
    Start-Process -FilePath $phpExe -WorkingDirectory $root -ArgumentList @("artisan", "serve", "--host=0.0.0.0", "--port=8000") -WindowStyle Minimized
    Write-Host "Started Laravel on http://0.0.0.0:8000 (minimized window)." -ForegroundColor Green
    Start-Sleep -Seconds 3
}

$cf = Get-Command cloudflared -ErrorAction SilentlyContinue
if (-not $cf) {
    Write-Host "Install: winget install -e --id Cloudflare.cloudflared" -ForegroundColor Yellow
    exit 1
}

Write-Host ""
Write-Host "Starting tunnel - your browser will open to the https://....trycloudflare.com link when it is ready." -ForegroundColor Green
Write-Host "Set SKIP_OPEN_TUNNEL_BROWSER=1 to disable auto-open. Ctrl+C to stop." -ForegroundColor DarkGray
Write-Host ""

$script:TunnelBrowserOpened = $false

$cfArgs = @(
    '--no-autoupdate', 'tunnel',
    '--proxy-connect-timeout', '2m', '--proxy-tls-timeout', '1m',
    '--url', 'http://127.0.0.1:8000'
)

# Stream stdout+stderr so we can detect the trycloudflare URL and Start-Process once (reliable in PowerShell 5.1).
$prevEa = $ErrorActionPreference
$ErrorActionPreference = 'Continue'
try {
    & $cf.Source @cfArgs 2>&1 | ForEach-Object {
        $line = if ($_ -is [System.Management.Automation.ErrorRecord]) {
            $_.ToString()
        } else {
            "$_"
        }
        $line = $line.TrimEnd("`r")
        if ([string]::IsNullOrWhiteSpace($line)) {
            return
        }
        Write-Host $line

        if ($script:TunnelBrowserOpened -or $env:SKIP_OPEN_TUNNEL_BROWSER -eq '1') {
            return
        }
        $m = [regex]::Match($line, 'https://[a-z0-9.-]+\.trycloudflare\.com')
        if ($m.Success) {
            $u = $m.Value
            Write-Host "Opening browser -> $u" -ForegroundColor Cyan
            try {
                Start-Process $u
            } catch {
                Write-Host "Could not start browser: $($_.Exception.Message)" -ForegroundColor Yellow
            }
            $script:TunnelBrowserOpened = $true
        }
    }
} finally {
    $ErrorActionPreference = $prevEa
}

if ($null -ne $LASTEXITCODE -and $LASTEXITCODE -ne 0) {
    exit $LASTEXITCODE
}
exit 0
