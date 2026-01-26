@echo off
echo ========================================
echo   BiznesPilot - Cloudflare Tunnel
echo ========================================
echo.

REM Cloudflared ni topish
set CLOUDFLARED=C:\Users\Umidbek\cloudflared.exe

if not exist "%CLOUDFLARED%" (
    echo [ERROR] cloudflared.exe topilmadi!
    echo Yuklab olish: https://developers.cloudflare.com/cloudflare-one/connections/connect-apps/install-and-setup/installation
    pause
    exit /b 1
)

echo [INFO] Tunnel ishga tushirilmoqda...
echo [INFO] URL ni kutib turing...
echo.

REM Tunnel ni ishga tushirish
"%CLOUDFLARED%" tunnel --url http://localhost:8000

pause
