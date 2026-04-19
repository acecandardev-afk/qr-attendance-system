# Offline / LAN-only: Laravel on :8000 + Caddy HTTPS on :9443 (phone camera needs https://LAN-IP:9443). Change $DevHttpsPort below + Caddyfile + LAN_HTTPS_PORT if you use another port.
# Run: "smart attendance start.bat"  |  Optional: -SkipBootstrap -SkipCaddy -NoBrowser -InstallShortcut

param(
    [switch]$NoBrowser,
    [switch]$InstallShortcut,
    [switch]$SkipBootstrap,
    [switch]$SkipCaddy,
    [switch]$SkipFirewallElevation
)

$ErrorActionPreference = "Stop"
$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

# Must match Caddyfile `https://:{port}` and Laravel config app.lan_https_port (.env LAN_HTTPS_PORT).
$DevHttpsPort = 9443

$env:Path = [System.Environment]::GetEnvironmentVariable("Path", "Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path", "User")

function Get-EnvFileValue {
    param([string]$Path, [string]$Key)
    if (-not (Test-Path $Path)) {
        return $null
    }
    $pattern = "^\s*" + [regex]::Escape($Key) + "\s*="
    $line = Get-Content -LiteralPath $Path -ErrorAction SilentlyContinue |
        Where-Object { $_ -match $pattern } |
        Select-Object -First 1
    if (-not $line) {
        return $null
    }
    $val = ($line -split "=", 2)[1].Trim()
    if (($val.StartsWith('"') -and $val.EndsWith('"')) -or ($val.StartsWith("'") -and $val.EndsWith("'"))) {
        $val = $val.Substring(1, $val.Length - 2)
    }

    return $val
}

function Test-PortListening {
    param([int]$Port)
    $conn = Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue |
        Select-Object -First 1
    return $null -ne $conn
}

function Get-PortListenerProcessName {
    param([int]$Port)
    $conn = Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue |
        Select-Object -First 1
    if (-not $conn) {
        return $null
    }
    try {
        return (Get-Process -Id $conn.OwningProcess -ErrorAction Stop).ProcessName
    } catch {
        return $null
    }
}

function Write-DevPortDiagnostics {
    Write-Host ""
    Write-Host "--- Ports (if $DevHttpsPort is not Caddy, phones get ERR_SSL_PROTOCOL_ERROR on https://) ---" -ForegroundColor White
    foreach ($port in @(8000, $DevHttpsPort)) {
        if (-not (Test-PortListening $port)) {
            Write-Host "  TCP $port : NOT listening" -ForegroundColor $(if ($port -eq 8000) { 'Red' } else { 'Yellow' })
            if ($port -eq $DevHttpsPort) {
                Write-Host "           Start Caddy (launcher should open a Caddy window) or install: winget install CaddyServer.Caddy" -ForegroundColor Yellow
            }
            continue
        }
        $proc = Get-PortListenerProcessName -Port $port
        if (-not $proc) {
            Write-Host "  TCP $port : listening (could not read process name)" -ForegroundColor DarkGray
            continue
        }
        if ($port -eq $DevHttpsPort) {
            if ($proc -ieq 'caddy') {
                Write-Host "  TCP $port : $proc  (HTTPS for phones is OK)" -ForegroundColor Green
            } else {
                Write-Host "  TCP $port : $proc  (NOT Caddy - this breaks HTTPS on phones)" -ForegroundColor Red
                Write-Host "           Stop that program or free port $DevHttpsPort, then run Smart Attendance again so Caddy owns $DevHttpsPort." -ForegroundColor Yellow
            }
        } else {
            Write-Host "  TCP $port : $proc" -ForegroundColor Green
        }
    }
    Write-Host ""
}

function Get-LanIPv4 {
    $detected = Get-NetIPAddress -AddressFamily IPv4 -ErrorAction SilentlyContinue |
        Where-Object {
            $_.IPAddress -notmatch '^127\.' -and
            $_.IPAddress -notmatch '^169\.254\.' -and
            $_.PrefixOrigin -ne 'WellKnown'
        } |
        Sort-Object InterfaceMetric
    return ($detected | Select-Object -First 1).IPAddress
}

# Inbound allow for Laravel :8000 and Caddy HTTPS. Without this, phones get ERR_CONNECTION_TIMED_OUT.
function Ensure-DevFirewallRules {
    param([switch]$SkipElevation)

    $ports = @(8000, $DevHttpsPort)
    $helper = Join-Path $PSScriptRoot "ensure-dev-firewall.ps1"

    function Test-AllRulesPresent {
        foreach ($port in $ports) {
            $name = "Smart Attendance dev TCP $port"
            if (-not (Get-NetFirewallRule -DisplayName $name -ErrorAction SilentlyContinue)) {
                return $false
            }
        }
        return $true
    }

    if (Test-AllRulesPresent) {
        Write-Host "Firewall: rules already present for TCP 8000 and $DevHttpsPort." -ForegroundColor DarkGray
        return
    }

    $prevEa = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    foreach ($port in $ports) {
        $name = "Smart Attendance dev TCP $port"
        if (Get-NetFirewallRule -DisplayName $name -ErrorAction SilentlyContinue) {
            continue
        }
        try {
            New-NetFirewallRule -DisplayName $name -Direction Inbound -Action Allow -Protocol TCP -LocalPort $port -Profile Any | Out-Null
            Write-Host "Firewall: allowed inbound TCP $port (other devices on Wi-Fi can reach this PC)." -ForegroundColor Green
        } catch {
            Write-Host "Firewall: need admin rights to open TCP $port." -ForegroundColor Yellow
        }
    }
    $ErrorActionPreference = $prevEa

    if (Test-AllRulesPresent) {
        return
    }

    if ($SkipElevation) {
        Write-Host "Firewall: add rules manually or run: scripts\ensure-dev-firewall.ps1 as Administrator." -ForegroundColor Yellow
        return
    }

    if (-not (Test-Path -LiteralPath $helper)) {
        Write-Host "Firewall: missing $helper" -ForegroundColor Red
        return
    }

    Write-Host ""
    Write-Host "A Windows prompt will ask for permission to allow Smart Attendance through the firewall (needed for phones on the same Wi-Fi). Click Yes once." -ForegroundColor Yellow
    try {
        $proc = Start-Process -FilePath "powershell.exe" -ArgumentList @(
            '-NoLogo', '-NoProfile', '-ExecutionPolicy', 'Bypass', '-File', $helper
        ) -Verb RunAs -Wait -PassThru
        if ($proc.ExitCode -ne 0) {
            Write-Host "Firewall: elevated script exited with code $($proc.ExitCode)." -ForegroundColor Yellow
        }
    } catch {
        Write-Host "Firewall: UAC was cancelled or elevation failed. Right-click the launcher - Run as administrator, or run scripts\ensure-dev-firewall.ps1 as Administrator." -ForegroundColor Yellow
    }

    if (Test-AllRulesPresent) {
        Write-Host "Firewall: done. Try your phone again on https://<this-PC-IP>:$DevHttpsPort" -ForegroundColor Green
    } else {
        Write-Host "Firewall: rules still missing. Open Windows Defender Firewall - Advanced - Inbound Rules - New Rule - Port - TCP 8000 and $DevHttpsPort - Allow." -ForegroundColor Yellow
    }
}

function Resolve-OpenUrl {
    param(
        [string]$ProjectRoot,
        [bool]$PreferHttpsLanEndpoint = $true
    )

    $envPath = Join-Path $ProjectRoot ".env"

    $explicit = Get-EnvFileValue $envPath "DEV_OPEN_URL"
    if ($explicit -and $explicit -match '^\s*https?://') {
        return $explicit.Trim().TrimEnd('/')
    }

    $devLan = $env:DEV_LAN_IP
    if (-not $devLan -or $devLan.Trim() -eq "") {
        $devLan = Get-EnvFileValue $envPath "DEV_LAN_IP"
    }
    if (-not $devLan -or $devLan.Trim() -eq "") {
        $devLan = Get-LanIPv4
    }

    $appUrl = Get-EnvFileValue $envPath "APP_URL"
    if ($appUrl -and $appUrl -match '^\s*https?://') {
        $u = $appUrl.Trim().TrimEnd('/')
        # APP_URL=http://localhost (no port) hits port 80, not artisan :8000
        if ($u -match '^https?://(localhost|127\.0\.0\.1)(/|$|\?|#)') {
            if ($devLan -and $devLan.Trim() -ne "" -and $devLan -ne '127.0.0.1') {
                if ($PreferHttpsLanEndpoint) {
                    return "https://${devLan}:$DevHttpsPort"
                }
                return "http://${devLan}:8000"
            }
            return $u -replace '^(https?://(?:localhost|127\.0\.0\.1))(?=/|$|\?|#)', '$1:8000'
        }
        return $u
    }

    if ($devLan -and $devLan.Trim() -ne "") {
        if ($PreferHttpsLanEndpoint) {
            return "https://${devLan}:$DevHttpsPort"
        }
        if ($devLan -ne '127.0.0.1') {
            return "http://${devLan}:8000"
        }
    }

    return "http://127.0.0.1:8000"
}

function Invoke-ProjectBootstrap {
    param([string]$ProjectRoot)

    Write-Host ""
    Write-Host "--- Project setup (install / migrate / assets) ---" -ForegroundColor Cyan

    $vendorAutoload = Join-Path $ProjectRoot "vendor\autoload.php"
    if (-not (Test-Path -LiteralPath $vendorAutoload)) {
        if (-not (Get-Command composer -ErrorAction SilentlyContinue)) {
            Write-Host "The 'vendor' folder is missing and Composer was not found on PATH." -ForegroundColor Red
            Write-Host "Either install Composer and add it to PATH, or copy the entire 'vendor' folder from a PC that ran 'composer install' (offline-friendly)." -ForegroundColor Yellow
            exit 1
        }
        Write-Host "Running composer install..." -ForegroundColor Yellow
        Push-Location $ProjectRoot
        try {
            & composer install --no-interaction --prefer-dist
        } finally {
            Pop-Location
        }
        if ($LASTEXITCODE -ne 0) {
            Write-Host "composer install failed (exit $LASTEXITCODE). For offline use, include 'vendor' when copying the project." -ForegroundColor Red
            exit 1
        }
    }

    $envExample = Join-Path $ProjectRoot ".env.example"
    $envFile = Join-Path $ProjectRoot ".env"
    if (-not (Test-Path -LiteralPath $envFile)) {
        if (-not (Test-Path -LiteralPath $envExample)) {
            Write-Host "Missing .env.example - cannot create .env" -ForegroundColor Red
            exit 1
        }
        Copy-Item -LiteralPath $envExample -Destination $envFile
        Write-Host "Created .env from .env.example" -ForegroundColor Green
    }

    $appKey = Get-EnvFileValue $envFile "APP_KEY"
    if (-not $appKey -or $appKey.Trim() -eq "") {
        Push-Location $ProjectRoot
        try {
            & php artisan key:generate --force
        } finally {
            Pop-Location
        }
        if ($LASTEXITCODE -ne 0) {
            Write-Host "php artisan key:generate failed." -ForegroundColor Red
            exit 1
        }
    }

    $dbConn = Get-EnvFileValue $envFile "DB_CONNECTION"
    if ($dbConn -eq 'sqlite') {
        $sqlitePath = Join-Path $ProjectRoot "database\database.sqlite"
        if (-not (Test-Path -LiteralPath $sqlitePath)) {
            $null = New-Item -ItemType File -Path $sqlitePath -Force
            Write-Host "Created database\database.sqlite" -ForegroundColor Green
        }
    }

    Push-Location $ProjectRoot
    try {
        & php artisan migrate --force
    } finally {
        Pop-Location
    }
    if ($LASTEXITCODE -ne 0) {
        Write-Host "php artisan migrate failed. Check database settings in .env" -ForegroundColor Red
        exit 1
    }

    $viteManifest = Join-Path $ProjectRoot "public\build\manifest.json"
    if (-not (Test-Path -LiteralPath $viteManifest)) {
        if (-not (Get-Command npm -ErrorAction SilentlyContinue)) {
            Write-Host "public\build\manifest.json is missing and npm was not found." -ForegroundColor Red
            Write-Host "Copy the 'public\build' folder from a machine where you ran 'npm run build', or install Node.js LTS and run 'npm install' + 'npm run build' once (needs network for npm)." -ForegroundColor Yellow
            exit 1
        }
        if (-not (Test-Path (Join-Path $ProjectRoot "node_modules"))) {
            Write-Host "Running npm install..." -ForegroundColor Yellow
            Push-Location $ProjectRoot
            try {
                & npm install
            } finally {
                Pop-Location
            }
            if ($LASTEXITCODE -ne 0) {
                Write-Host "npm install failed. For offline installs, copy 'node_modules' and 'public\build' from a dev PC." -ForegroundColor Red
                exit 1
            }
        }
        Write-Host "Running npm run build..." -ForegroundColor Yellow
        Push-Location $ProjectRoot
        try {
            & npm run build
        } finally {
            Pop-Location
        }
        if ($LASTEXITCODE -ne 0 -or -not (Test-Path -LiteralPath $viteManifest)) {
            Write-Host "npm run build did not produce public\build\manifest.json." -ForegroundColor Red
            exit 1
        }
    }

    Push-Location $ProjectRoot
    try {
        & php artisan config:clear 2>$null
        & php artisan optimize:clear 2>$null
    } finally {
        Pop-Location
    }

    Write-Host "--- Setup complete ---" -ForegroundColor Green
    Write-Host ""
}

function Normalize-OpenUrl {
    param([string]$Url, [string]$Fallback)
    $u = if ($null -eq $Url) { "" } else { $Url.Trim() }
    if ($u -eq "" -or $u -notmatch "^https?://") {
        return $Fallback
    }
    return $u
}

# --- Create desktop shortcut and exit ---
if ($InstallShortcut) {
    $WshShell = New-Object -ComObject WScript.Shell
    $desktop = [Environment]::GetFolderPath("Desktop")
    $lnkPath = Join-Path $desktop "Smart Attendance start.lnk"
    $launcherBat = Join-Path $root "smart attendance start.bat"
    $shortcut = $WshShell.CreateShortcut($lnkPath)
    if (Test-Path -LiteralPath $launcherBat) {
        $shortcut.TargetPath = $launcherBat
        $shortcut.Arguments = ""
    } else {
        Write-Host "Could not find smart attendance start.bat" -ForegroundColor Red
        exit 1
    }
    $shortcut.WorkingDirectory = $root
    $shortcut.Description = "Smart Attendance - offline LAN (Laravel + Caddy)"
    $shortcut.Save()
    Write-Host "Shortcut created: $lnkPath" -ForegroundColor Green
    exit 0
}

if (-not (Get-Command php -ErrorAction SilentlyContinue)) {
    Write-Host "PHP was not found on PATH. Install PHP or add it to PATH, then try again." -ForegroundColor Red
    exit 1
}

if (-not $SkipBootstrap) {
    Invoke-ProjectBootstrap -ProjectRoot $root
}

# Caddy + run-caddy.ps1 expect this for TLS cert SAN
if (-not $env:CADDY_LAN_IP -or $env:CADDY_LAN_IP.Trim() -eq "") {
    $fromEnv = Get-EnvFileValue (Join-Path $root ".env") "DEV_LAN_IP"
    if ($fromEnv) {
        $env:CADDY_LAN_IP = $fromEnv.Trim()
    }
}
if (-not $env:CADDY_LAN_IP -or $env:CADDY_LAN_IP.Trim() -eq "") {
    $env:CADDY_LAN_IP = Get-LanIPv4
}
if (-not $env:CADDY_LAN_IP -or $env:CADDY_LAN_IP.Trim() -eq "") {
    Write-Host "Could not detect LAN IPv4. Add to .env: DEV_LAN_IP=192.168.x.x  or set `$env:CADDY_LAN_IP" -ForegroundColor Yellow
    $env:CADDY_LAN_IP = "127.0.0.1"
}

# Resolve Caddy when winget installed it but this shell was not restarted (common with .bat launch)
$caddyExe = $null
if (-not $SkipCaddy) {
    $cmd = Get-Command caddy -ErrorAction SilentlyContinue
    if ($cmd) {
        $caddyExe = $cmd.Source
    }
    if (-not $caddyExe) {
        $caddySearchDirs = @(
            (Join-Path $env:ProgramFiles "Caddy"),
            (Join-Path ([Environment]::GetFolderPath('ProgramFilesX86')) "Caddy")
        )
        foreach ($dir in $caddySearchDirs) {
            $p = Join-Path $dir "caddy.exe"
            if (Test-Path -LiteralPath $p) {
                $env:Path = "$dir;$env:Path"
                $caddyExe = $p
                break
            }
        }
    }
}
$hasCaddyCmd = (-not $SkipCaddy) -and ($null -ne $caddyExe)

if (-not $SkipCaddy -and -not $hasCaddyCmd) {
    Write-Host "" 
    Write-Host "Caddy was not found. Phone camera and QR scanning need HTTPS (project Caddyfile on port $DevHttpsPort)." -ForegroundColor Yellow
    Write-Host "  Install: winget install -e --id CaddyServer.Caddy" -ForegroundColor Cyan
    Write-Host "  Then run Smart Attendance again, or add Caddy to PATH and reopen the terminal." -ForegroundColor Yellow
    Write-Host "  HTTP-only (camera often blocked on phones): re-run with -SkipCaddy" -ForegroundColor DarkGray
    Write-Host ""
}

$lanTrim = $env:CADDY_LAN_IP.Trim()

# Laravel reads this for URL generation / CSRF; must match the URL you open in the browser.
if ($hasCaddyCmd) {
    if ($lanTrim -and $lanTrim -ne '127.0.0.1') {
        $env:APP_URL = "https://${lanTrim}:$DevHttpsPort"
    } else {
        $env:APP_URL = "https://127.0.0.1:$DevHttpsPort"
    }
} elseif ($lanTrim -and $lanTrim -ne '127.0.0.1') {
    $env:APP_URL = "http://${lanTrim}:8000"
} else {
    $env:APP_URL = "http://127.0.0.1:8000"
}

Write-Host "Project: $root" -ForegroundColor Cyan
Write-Host "CADDY_LAN_IP=$($env:CADDY_LAN_IP) (override: `$env:CADDY_LAN_IP or DEV_LAN_IP in .env)" -ForegroundColor Green
Write-Host "APP_URL for this Laravel process: $($env:APP_URL)" -ForegroundColor DarkGray

$openUrl = Normalize-OpenUrl -Url (Resolve-OpenUrl -ProjectRoot $root -PreferHttpsLanEndpoint:$hasCaddyCmd) -Fallback "http://127.0.0.1:8000"

$caddyScript = Join-Path $root "scripts\run-caddy.ps1"
if (-not (Test-Path $caddyScript)) {
    Write-Host "Missing scripts\run-caddy.ps1" -ForegroundColor Red
    exit 1
}

Ensure-DevFirewallRules -SkipElevation:$SkipFirewallElevation

# Listen on all interfaces so other devices on the LAN can use http://YOUR_LAN_IP:8000.
# Caddy still proxies to 127.0.0.1:8000; binding 0.0.0.0 does not break that.
$serveHost = "0.0.0.0"
if (-not (Test-PortListening 8000)) {
    $phpExe = (Get-Command php -ErrorAction Stop).Source
    Start-Process -FilePath $phpExe -WorkingDirectory $root -ArgumentList @("artisan", "serve", "--host=$serveHost", "--port=8000") -WindowStyle Minimized
    Write-Host "Started PHP artisan serve on ${serveHost}:8000 (minimized window) - reachable on your LAN at http://<this-PC-LAN-IP>:8000." -ForegroundColor Green
    Start-Sleep -Seconds 2
} else {
    Write-Host "Port 8000 already in use - assuming Laravel is running (APP_URL above may not apply to that process)." -ForegroundColor Yellow
    Write-Host "If phones get stuck after login or HTTPS fails: close that PHP window/process, then run this launcher again so Laravel picks up APP_URL for this session." -ForegroundColor DarkYellow
}

$lanShow = $env:CADDY_LAN_IP.Trim()
if ($lanShow -and $lanShow -ne '127.0.0.1') {
    Write-Host ""
    Write-Host "----- Same Wi-Fi (use this PC's IPv4: $lanShow) -----" -ForegroundColor White
    Write-Host "  Phone HTTPS (QR / camera):        https://${lanShow}:$DevHttpsPort  <- HTTPS is on this port (Caddy), not on :8000." -ForegroundColor Cyan
    Write-Host "  Phone HTTP (no camera):           http://${lanShow}:8000  <- if you use https on :8000 you get ERR_SSL_PROTOCOL_ERROR." -ForegroundColor Yellow
    Write-Host "  This PC:                          http://127.0.0.1:8000  (HTTPS is only on port $DevHttpsPort, not 8000)" -ForegroundColor Cyan
    Write-Host "  Optional on this PC (HTTPS):      https://127.0.0.1:$DevHttpsPort  only if Caddy is running" -ForegroundColor DarkGray
    Write-Host "  Never use https:// on port 8000 - you will get ERR_SSL_PROTOCOL_ERROR." -ForegroundColor DarkYellow
    Write-Host "  (Do not use the LAN IP in the browser on this PC - it often times out.)" -ForegroundColor DarkYellow
    Write-Host ""
}

if (-not (Test-PortListening $DevHttpsPort)) {
    if (-not $hasCaddyCmd) {
        Write-Host "Caddy is not installed or not on PATH. HTTPS for phones/QR: winget install -e --id CaddyServer.Caddy" -ForegroundColor Yellow
        $lanForHttp = $env:CADDY_LAN_IP.Trim()
        if ($lanForHttp -and $lanForHttp -ne '127.0.0.1') {
            $openUrl = "http://${lanForHttp}:8000"
            Write-Host "Serving HTTP on all interfaces - open from this PC or LAN: $openUrl" -ForegroundColor Yellow
        } else {
            $openUrl = "http://127.0.0.1:8000"
            Write-Host "Opening: $openUrl" -ForegroundColor Yellow
        }
    } else {
        # run-caddy.ps1 reads env:CADDY_LAN_IP from the inherited environment (uses -File, no inline script).
        if ([string]::IsNullOrWhiteSpace($env:CADDY_LAN_IP)) {
            $env:CADDY_LAN_IP = "127.0.0.1"
        } else {
            $env:CADDY_LAN_IP = $env:CADDY_LAN_IP.Trim()
        }
        Start-Process -FilePath "powershell.exe" -WorkingDirectory $root -ArgumentList @(
            "-NoLogo", "-NoExit", "-ExecutionPolicy", "Bypass", "-File", $caddyScript
        ) -WindowStyle Normal
        Write-Host "Started Caddy (see window for HTTPS on :$DevHttpsPort)." -ForegroundColor Green
        Start-Sleep -Seconds 4
    }
} else {
    Write-Host "Port $DevHttpsPort already in use - checking which program is listening..." -ForegroundColor Yellow
}

Write-DevPortDiagnostics

if ((Test-PortListening $DevHttpsPort) -and ((Get-PortListenerProcessName -Port $DevHttpsPort) -ieq 'caddy')) {
    $hintLan = $env:CADDY_LAN_IP.Trim()
    if ([string]::IsNullOrWhiteSpace($hintLan) -or $hintLan -eq '127.0.0.1') {
        $hintLan = Get-LanIPv4
    }
    if ($hintLan) {
        Write-Host "If the phone still shows ERR_SSL_PROTOCOL_ERROR on https://${hintLan}:$DevHttpsPort :" -ForegroundColor Yellow
        Write-Host "  - Run ipconfig on THIS PC: the IPv4 must match $hintLan in the URL." -ForegroundColor DarkYellow
        Write-Host "  - On the phone: turn mobile data OFF (Wi-Fi only to the same router as this PC)." -ForegroundColor DarkYellow
        Write-Host "  - Close the Caddy window and run Smart Attendance again (reloads Caddyfile after updates)." -ForegroundColor DarkYellow
        Write-Host "  - If http://${hintLan}:8000 works but https fails, something else was answering on $DevHttpsPort until you restarted Caddy." -ForegroundColor DarkYellow
        Write-Host ""
    }
}

# If nothing is listening on the HTTPS port, do not open an https://... URL (e.g. Caddy failed to start).
if (-not (Test-PortListening $DevHttpsPort) -and ($openUrl -match (':' + [string]$DevHttpsPort))) {
    $fallbackLan = $env:CADDY_LAN_IP.Trim()
    if ($fallbackLan -and $fallbackLan -ne '127.0.0.1') {
        $openUrl = "http://${fallbackLan}:8000"
    } else {
        $openUrl = "http://127.0.0.1:8000"
    }
    Write-Host "HTTPS not available on :$DevHttpsPort - opening $openUrl instead." -ForegroundColor Yellow
}

if (-not $NoBrowser) {
    $openUrl = Normalize-OpenUrl -Url $openUrl -Fallback "http://127.0.0.1:8000"
    $devOpenRaw = Get-EnvFileValue (Join-Path $root ".env") "DEV_OPEN_URL"
    if ($devOpenRaw -and ($devOpenRaw.Trim() -match '^\s*https?://')) {
        # Optional .env override (any URL you want the launcher to open on this PC).
        $openUrl = $devOpenRaw.Trim().TrimEnd('/')
    } elseif (Test-PortListening 8000) {
        # This PC: always HTTP on :8000. Port 8000 has no TLS; https://127.0.0.1:8000 causes ERR_SSL_PROTOCOL_ERROR.
        $openUrl = "http://127.0.0.1:8000"
    } elseif (Test-PortListening $DevHttpsPort) {
        # Laravel not up yet but Caddy is - rare; HTTPS only on Caddy's port, never on 8000.
        $openUrl = "https://127.0.0.1:$DevHttpsPort"
    }
    $openUrl = Normalize-OpenUrl -Url $openUrl -Fallback "http://127.0.0.1:8000"
    Write-Host "Opening browser on this PC: $openUrl" -ForegroundColor Green
    Start-Process $openUrl
}

Write-Host 'Done. Close the Laravel and Caddy windows when you are finished.' -ForegroundColor Cyan
