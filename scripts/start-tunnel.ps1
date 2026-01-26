# BiznesPilot - Cloudflare Tunnel Starter
# Bu script tunnel ni ishga tushiradi va webhook ni avtomatik yangilaydi

param(
    [switch]$UpdateWebhook = $false
)

$CloudflaredPath = "C:\Users\Umidbek\cloudflared.exe"
$LocalUrl = "http://localhost:8000"
$LogFile = "$env:TEMP\cloudflared_tunnel.log"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  BiznesPilot - Cloudflare Tunnel" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Cloudflared mavjudligini tekshirish
if (-not (Test-Path $CloudflaredPath)) {
    Write-Host "[ERROR] cloudflared.exe topilmadi: $CloudflaredPath" -ForegroundColor Red
    Write-Host "Yuklab olish: https://developers.cloudflare.com/cloudflare-one/connections/connect-apps/install-and-setup/installation" -ForegroundColor Yellow
    exit 1
}

# Eski tunnel ni to'xtatish
Write-Host "[INFO] Eski tunnel to'xtatilmoqda..." -ForegroundColor Yellow
Get-Process -Name cloudflared -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue
Start-Sleep -Seconds 2

# Log faylni tozalash
if (Test-Path $LogFile) {
    Remove-Item $LogFile -Force
}

Write-Host "[INFO] Tunnel ishga tushirilmoqda..." -ForegroundColor Green
Write-Host "[INFO] URL ni kutib turing..." -ForegroundColor Yellow
Write-Host ""

# Tunnel ni background da ishga tushirish
$process = Start-Process -FilePath $CloudflaredPath -ArgumentList "tunnel", "--url", $LocalUrl -RedirectStandardError $LogFile -WindowStyle Hidden -PassThru

# URL ni kutish
$maxWait = 30
$waited = 0
$tunnelUrl = $null

while ($waited -lt $maxWait -and -not $tunnelUrl) {
    Start-Sleep -Seconds 1
    $waited++

    if (Test-Path $LogFile) {
        $content = Get-Content $LogFile -Raw -ErrorAction SilentlyContinue
        if ($content -match 'https://[a-z0-9-]+\.trycloudflare\.com') {
            $tunnelUrl = $matches[0]
        }
    }

    Write-Host "." -NoNewline -ForegroundColor Gray
}

Write-Host ""
Write-Host ""

if ($tunnelUrl) {
    Write-Host "========================================" -ForegroundColor Green
    Write-Host "  TUNNEL TAYYOR!" -ForegroundColor Green
    Write-Host "========================================" -ForegroundColor Green
    Write-Host ""
    Write-Host "  URL: $tunnelUrl" -ForegroundColor Cyan
    Write-Host ""

    # Clipboard ga nusxalash
    $tunnelUrl | Set-Clipboard
    Write-Host "[INFO] URL clipboard ga nusxalandi!" -ForegroundColor Yellow

    # Webhook yangilash
    if ($UpdateWebhook) {
        Write-Host ""
        Write-Host "[INFO] Webhook yangilanmoqda..." -ForegroundColor Yellow

        # .env faylni yangilash
        $envFile = "d:\biznespilot\.env"
        $envContent = Get-Content $envFile -Raw
        $envContent = $envContent -replace 'APP_URL=https://[a-z0-9-]+\.trycloudflare\.com', "APP_URL=$tunnelUrl"
        Set-Content -Path $envFile -Value $envContent -NoNewline

        Write-Host "[INFO] .env fayli yangilandi" -ForegroundColor Green

        # Laravel cache tozalash va webhook o'rnatish
        Set-Location "d:\biznespilot"
        php artisan config:clear

        $webhookCmd = @"
`$service = new App\Services\Telegram\SystemBotService();
`$result = `$service->setWebhook('$tunnelUrl/api/webhooks/system-bot');
echo `$result ? 'Webhook SUCCESS' : 'Webhook FAILED';
"@

        $result = php artisan tinker --execute="$webhookCmd"
        Write-Host "[INFO] $result" -ForegroundColor Green
    }

    Write-Host ""
    Write-Host "========================================" -ForegroundColor Cyan
    Write-Host "  Tunnel ishlayapti. Ctrl+C bosib to'xtating." -ForegroundColor Cyan
    Write-Host "========================================" -ForegroundColor Cyan

    # Tunnel jarayonini kuzatish
    Wait-Process -Id $process.Id

} else {
    Write-Host "[ERROR] Tunnel URL ni olib bo'lmadi!" -ForegroundColor Red
    Write-Host "Log fayl: $LogFile" -ForegroundColor Yellow

    if (Test-Path $LogFile) {
        Write-Host ""
        Write-Host "Log:" -ForegroundColor Yellow
        Get-Content $LogFile | Select-Object -First 20
    }
}
