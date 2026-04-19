@echo off
setlocal
title Smart Attendance - offline same Wi-Fi
cd /d "%~dp0"

if not exist "%~dp0smart attendance start.ps1" (
    echo ERROR: Could not find "smart attendance start.ps1" next to this batch file.
    echo Folder: %CD%
    pause
    exit /b 1
)

if not exist "%~dp0scripts\start-dev-stack.ps1" (
    echo ERROR: Missing scripts\start-dev-stack.ps1
    pause
    exit /b 1
)

echo.
echo OFFLINE / same Wi-Fi: Laravel on :8000 + Caddy HTTPS on :9443 (phones need https://YOUR_PC_IP:9443 for camera).
echo This PC: http://127.0.0.1:8000  -  Approve firewall/UAC if asked.
echo Internet + Cloudflare tunnel: run "easy phone HTTPS.bat" or "public class.bat"
echo.

powershell.exe -NoLogo -NoProfile -ExecutionPolicy Bypass -File "%~dp0smart attendance start.ps1" %*

set "EXITCODE=%ERRORLEVEL%"
if not "%EXITCODE%"=="0" (
    echo.
    echo [Stopped] Exit code %EXITCODE%. Read the messages above, fix the issue, then try again.
    pause
    exit /b %EXITCODE%
)

echo.
echo Done. You can close this window.
timeout /t 4 /nobreak >nul
exit /b 0
