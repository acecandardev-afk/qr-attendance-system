@echo off
title Smart Attendance - Cloudflare Tunnel (internet)
cd /d "%~dp0"

if not exist "%~dp0scripts\phone-https-cloudflared.ps1" (
    echo Missing scripts\phone-https-cloudflared.ps1
    pause
    exit /b 1
)

echo.
echo Needs internet. Gives https://....trycloudflare.com for phones (camera works).
echo Offline same Wi-Fi: use "smart attendance start.bat" instead.
echo.

powershell.exe -NoLogo -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\phone-https-cloudflared.ps1"

echo.
pause
