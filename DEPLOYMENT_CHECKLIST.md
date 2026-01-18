# BiznesPilot - Deployment Checklist

## KRITIK: Darhol bajarilishi kerak

### 1. API Kalitlarni yangilash
Server deploy qilinganidan keyin **.env** faylida eski API kalitlar ishlatilgan bo'lishi mumkin.

**Qilish kerak:**
```bash
# Serverga SSH orqali kiring
ssh deploy@your-server

# .env faylni tahrirlang
nano /var/www/biznespilot/.env
```

O'zgartirish kerak bo'lgan qiymatlar:
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://biznespilot.uz`
- `SESSION_ENCRYPT=true`
- `SESSION_SECURE_COOKIE=true`

### 2. Cache tozalash
```bash
cd /var/www/biznespilot
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 3. Migratsiyalarni ishga tushirish
```bash
php artisan migrate --force
```

### 4. Queue worker qayta ishga tushirish
```bash
php artisan queue:restart
sudo supervisorctl restart all
```

---

## Production .env namunasi

`.env.production.example` faylidan nusxa oling:
```bash
cp .env.production.example .env
php artisan key:generate
```

---

## Tuzatilgan xatolar (2026-01-18)

### 1. DashboardController TypeError
**Fayl:** `app/Http/Controllers/Marketing/DashboardController.php:81`

**Muammo:** `getCampaignStats($businessId)` funksiyasi `int` turini kutardi, lekin Business modeli UUID (string) ishlatadi.

**Yechim:** Type hint `int` dan `string` ga o'zgartirildi.

### 2. Performance Indexes Migration
**Fayl:** `database/migrations/2026_01_13_234031_add_performance_indexes.php`

**Muammo:** `campaigns` jadvalidagi `start_date` va `end_date` ustunlari aslida `starts_at` va `ends_at` deb nomlangan.

**Yechim:** Index yaratish ustun nomlarini to'g'rilash va xavfsiz tekshiruvlar qo'shildi.

---

## Server sozlamalari

### Nginx konfiguratsiyasi
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name biznespilot.uz www.biznespilot.uz;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name biznespilot.uz www.biznespilot.uz;

    root /var/www/biznespilot/public;
    index index.php;

    ssl_certificate /etc/letsencrypt/live/biznespilot.uz/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/biznespilot.uz/privkey.pem;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### Supervisor konfiguratsiyasi (Queue Workers)
```ini
[program:biznespilot-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/biznespilot/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/biznespilot/storage/logs/worker.log
stopwaitsecs=3600
```

---

## Health Check Endpoints

| Endpoint | Vazifasi |
|----------|----------|
| `/health/ping` | Oddiy 200 OK javob |
| `/health/status` | Batafsil holat (DB, Cache, Redis, Queue) |
| `/health/ready` | Load balancer uchun readiness probe |
| `/health/live` | Kubernetes uchun liveness probe |

---

## GitHub Actions Secrets

Quyidagi secretlarni GitHub repository settings'da sozlang:

| Secret | Tavsif |
|--------|--------|
| `SSH_PRIVATE_KEY` | Deploy uchun SSH private key |
| `SERVER_HOST` | Server IP yoki domain |
| `SERVER_USER` | SSH foydalanuvchi nomi |
| `DEPLOY_PATH` | /var/www/biznespilot |
| `HEALTH_URL` | https://biznespilot.uz |
| `TELEGRAM_BOT_TOKEN` | Xabarlar uchun (ixtiyoriy) |
| `TELEGRAM_CHAT_ID` | Telegram chat ID (ixtiyoriy) |

---

## Deploy qilish

### Manual Deploy (GitHub Actions)
1. GitHub > Actions > "Deploy to Production" workflow
2. "Run workflow" tugmasini bosing
3. Kerakli parametrlarni tanlang
4. Deploy jarayonini kuzating

### SSH orqali Deploy
```bash
ssh deploy@biznespilot.uz
cd /var/www/biznespilot
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
sudo chown -R www-data:www-data storage bootstrap/cache
```

---

## Monitoring

### Log fayllarni tekshirish
```bash
# Laravel logs
tail -f /var/www/biznespilot/storage/logs/laravel.log

# Queue worker logs
tail -f /var/www/biznespilot/storage/logs/worker.log

# Nginx error logs
tail -f /var/log/nginx/error.log
```

### Disk usage
```bash
df -h
du -sh /var/www/biznespilot/storage/logs
```

---

*Oxirgi yangilanish: 2026-01-18*
