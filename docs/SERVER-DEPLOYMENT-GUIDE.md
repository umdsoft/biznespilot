# BiznesPilot — Server Deployment Guide

> **Loyiha:** Laravel 12 + Vue 3 + Inertia.js + Tailwind CSS 4
> **Server:** Ubuntu 22.04/24.04 LTS | 1-2GB RAM VPS
> **Domain:** biznespilot.uz
> **Repo:** git@github.com:umdsoft/biznespilot.git

Barcha buyruqlarni yuqoridan pastga ketma-ket bajaring.

---

## 1. Serverga ulanish (root)

```bash
ssh root@SERVER_IP
```

---

## 2. Tizimni yangilash

```bash
apt update && apt upgrade -y
apt autoremove -y
```

---

## 3. Timezone

```bash
timedatectl set-timezone Asia/Tashkent
timedatectl
```

---

## 4. Bazaviy paketlar

```bash
apt install -y \
    curl \
    wget \
    git \
    unzip \
    zip \
    supervisor \
    ufw \
    fail2ban \
    htop \
    ncdu \
    acl \
    software-properties-common \
    apt-transport-https \
    ca-certificates \
    gnupg \
    lsb-release \
    certbot \
    python3-certbot-nginx \
    logrotate
```

---

## 5. Swap yaratish (MAJBURIY — 1-2GB VPS)

> Swapsiz composer install va queue worker'lar OOM qiladi.

```bash
fallocate -l 2G /swapfile
chmod 600 /swapfile
mkswap /swapfile
swapon /swapfile
```

Qayta yuklashda ham ishlashi uchun:

```bash
echo '/swapfile none swap sw 0 0' >> /etc/fstab
```

Swappiness optimallash:

```bash
echo 'vm.swappiness=10' >> /etc/sysctl.conf
echo 'vm.vfs_cache_pressure=50' >> /etc/sysctl.conf
sysctl -p
```

Tekshirish:

```bash
free -h
```

Natija: Swap satri 2.0Gi ko'rsatishi kerak.

---

## 6. Deploy user yaratish

```bash
adduser --disabled-password --gecos "" deploy
usermod -aG sudo deploy
usermod -aG www-data deploy
```

Sudo parolsiz (faqat servislar uchun):

```bash
cat > /etc/sudoers.d/deploy << 'EOF'
deploy ALL=(ALL) NOPASSWD: /usr/sbin/service, /bin/systemctl, /usr/bin/supervisorctl
EOF
chmod 440 /etc/sudoers.d/deploy
```

SSH katalog:

```bash
mkdir -p /home/deploy/.ssh
touch /home/deploy/.ssh/authorized_keys
chmod 700 /home/deploy/.ssh
chmod 600 /home/deploy/.ssh/authorized_keys
chown -R deploy:deploy /home/deploy/.ssh
```

---

## 7. SSH xavfsizlik

```bash
cp /etc/ssh/sshd_config /etc/ssh/sshd_config.bak
```

```bash
cat > /etc/ssh/sshd_config.d/hardening.conf << 'EOF'
Port 2222
PermitRootLogin prohibit-password
PasswordAuthentication yes
PubkeyAuthentication yes
MaxAuthTries 5
ClientAliveInterval 300
ClientAliveCountMax 2
X11Forwarding no
AllowUsers root deploy
EOF
```

```bash
systemctl restart sshd
```

> **MUHIM:** Hozirgi terminalni YOPMANG! Yangi terminal ochib tekshiring:
> `ssh -p 2222 root@SERVER_IP`
> Ishlasa — davom eting. Ishlamasa — eski terminalda tuzating.

Keyinroq SSH key o'rnatgandan keyin `PasswordAuthentication no` qiling.

---

## 8. Lokal kompyuterdan SSH key qo'shish

**Lokal kompyuterda** (PowerShell / Git Bash):

```bash
ssh-keygen -t ed25519 -C "deploy@biznespilot"
```

Keyni serverga yuborish:

```bash
ssh-copy-id -p 2222 deploy@SERVER_IP
```

Yoki qo'lda — lokaldagi keyni ko'rish:

```bash
cat ~/.ssh/id_ed25519.pub
```

**Serverda** root sifatida:

```bash
nano /home/deploy/.ssh/authorized_keys
```

Lokal kompyuterdan ko'chirilgan public key ni paste qiling, saqlang.

Tekshirish (lokal kompyuterdan):

```bash
ssh -p 2222 deploy@SERVER_IP
```

---

## 9. PHP 8.2 o'rnatish

```bash
add-apt-repository -y ppa:ondrej/php
apt update
```

```bash
apt install -y \
    php8.2-fpm \
    php8.2-cli \
    php8.2-mysql \
    php8.2-redis \
    php8.2-mbstring \
    php8.2-xml \
    php8.2-curl \
    php8.2-zip \
    php8.2-gd \
    php8.2-intl \
    php8.2-bcmath \
    php8.2-opcache \
    php8.2-readline \
    php8.2-tokenizer
```

Tekshirish:

```bash
php -v
php -m | grep -E "redis|gd|intl|bcmath|opcache"
```

### 9.1. php.ini (CLI)

```bash
sed -i 's/memory_limit = .*/memory_limit = 256M/' /etc/php/8.2/cli/php.ini
sed -i 's/max_execution_time = .*/max_execution_time = 300/' /etc/php/8.2/cli/php.ini
```

### 9.2. php.ini (FPM)

```bash
sed -i 's/memory_limit = .*/memory_limit = 128M/' /etc/php/8.2/fpm/php.ini
sed -i 's/max_execution_time = .*/max_execution_time = 180/' /etc/php/8.2/fpm/php.ini
sed -i 's/upload_max_filesize = .*/upload_max_filesize = 64M/' /etc/php/8.2/fpm/php.ini
sed -i 's/post_max_size = .*/post_max_size = 64M/' /etc/php/8.2/fpm/php.ini
sed -i 's/expose_php = .*/expose_php = Off/' /etc/php/8.2/fpm/php.ini
sed -i 's|;date.timezone =|date.timezone = Asia/Tashkent|' /etc/php/8.2/fpm/php.ini
```

### 9.3. OPcache + JIT

```bash
cat > /etc/php/8.2/fpm/conf.d/99-opcache-custom.ini << 'EOF'
[opcache]
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.revalidate_freq=0
opcache.validate_timestamps=0
opcache.save_comments=1
opcache.enable_file_override=1
opcache.jit=1255
opcache.jit_buffer_size=64M
EOF
```

> `validate_timestamps=0` — deploy dan keyin `systemctl reload php8.2-fpm` kerak. `deploy.sh` buni avtomatik qiladi.

### 9.4. PHP-FPM pool (Low-Memory)

Default pool o'chirish:

```bash
mv /etc/php/8.2/fpm/pool.d/www.conf /etc/php/8.2/fpm/pool.d/www.conf.disabled
```

BiznesPilot pool yaratish:

```bash
cat > /etc/php/8.2/fpm/pool.d/biznespilot.conf << 'EOF'
[biznespilot]
user = deploy
group = www-data

listen = /run/php/php-fpm-biznespilot.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660

; 1-2GB VPS uchun OPTIMALLASHTIRILGAN
pm = ondemand
pm.max_children = 5
pm.process_idle_timeout = 20s
pm.max_requests = 500

request_terminate_timeout = 180
request_slowlog_timeout = 10s
slowlog = /var/log/php-fpm/biznespilot-slow.log

php_admin_value[error_log] = /var/log/php-fpm/biznespilot-error.log
php_admin_flag[log_errors] = on

php_admin_value[expose_php] = Off
php_admin_value[display_errors] = Off
php_admin_value[display_startup_errors] = Off
php_admin_value[open_basedir] = /var/www/biznespilot:/tmp:/var/lib/php/sessions

env[APP_ENV] = production
env[APP_DEBUG] = false
EOF
```

```bash
mkdir -p /var/log/php-fpm
systemctl restart php8.2-fpm
systemctl enable php8.2-fpm
systemctl status php8.2-fpm
```

---

## 10. Composer

```bash
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer
composer --version
```

---

## 11. MySQL

```bash
apt install -y mysql-server
systemctl enable mysql
systemctl start mysql
```

### 11.1. Xavfsizlik

```bash
mysql_secure_installation
```

Javoblar:
- VALIDATE PASSWORD: **Y** → **1** (MEDIUM)
- Root parol: **kuchli parol o'rnating**
- Remove anonymous users: **Y**
- Disallow root login remotely: **Y**
- Remove test database: **Y**
- Reload privilege tables: **Y**

### 11.2. Database va user yaratish

```bash
mysql
```

```sql
CREATE DATABASE biznespilot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'biznespilot'@'localhost' IDENTIFIED BY 'SIZ_TANLAGAN_KUCHLI_PAROL';
GRANT ALL PRIVILEGES ON biznespilot.* TO 'biznespilot'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

> Bu parolni yozing — `.env` da `DB_PASSWORD` ga kerak bo'ladi.

### 11.3. MySQL tuning (1-2GB VPS)

```bash
cat > /etc/mysql/mysql.conf.d/biznespilot.cnf << 'EOF'
[mysqld]
innodb_buffer_pool_size = 256M
innodb_log_buffer_size = 16M
innodb_log_file_size = 64M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT

max_connections = 50
wait_timeout = 300
interactive_timeout = 300

tmp_table_size = 32M
max_heap_table_size = 32M
sort_buffer_size = 2M
join_buffer_size = 2M
read_rnd_buffer_size = 2M

slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2

character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci

local_infile = 0
skip-symbolic-links = 1
EOF
```

```bash
systemctl restart mysql
```

---

## 12. Redis

```bash
apt install -y redis-server
```

### 12.1. Redis optimallash

```bash
nano /etc/redis/redis.conf
```

Quyidagi qatorlarni toping va o'zgartiring (yoki oxiriga qo'shing):

```
bind 127.0.0.1 ::1
maxmemory 128mb
maxmemory-policy allkeys-lru
```

```bash
systemctl restart redis-server
systemctl enable redis-server
redis-cli ping
```

Javob: `PONG`

---

## 13. Nginx

```bash
apt install -y nginx
systemctl enable nginx
```

### 13.1. Asosiy konfiguratsiya

```bash
cat > /etc/nginx/nginx.conf << 'EOF'
user www-data;
worker_processes auto;
pid /run/nginx.pid;
include /etc/nginx/modules-enabled/*.conf;

events {
    worker_connections 1024;
    multi_accept on;
    use epoll;
}

http {
    sendfile on;
    tcp_nopush on;
    tcp_nodelay on;
    keepalive_timeout 30;
    types_hash_max_size 2048;
    server_tokens off;
    client_max_body_size 64M;

    include /etc/nginx/mime.types;
    default_type application/octet-stream;

    access_log /var/log/nginx/access.log;
    error_log /var/log/nginx/error.log;

    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_min_length 256;
    gzip_types
        text/plain
        text/css
        text/xml
        text/javascript
        application/json
        application/javascript
        application/xml
        application/rss+xml
        application/atom+xml
        image/svg+xml
        font/woff2;

    limit_req_zone $binary_remote_addr zone=general:10m rate=10r/s;
    limit_req_zone $binary_remote_addr zone=api:10m rate=30r/s;
    limit_req_zone $binary_remote_addr zone=login:10m rate=5r/m;

    include /etc/nginx/conf.d/*.conf;
    include /etc/nginx/sites-enabled/*;
}
EOF
```

### 13.2. Sayt konfiguratsiyasi (avval HTTP, SSL keyinroq)

```bash
cat > /etc/nginx/sites-available/biznespilot << 'EOF'
server {
    listen 80;
    listen [::]:80;
    server_name biznespilot.uz www.biznespilot.uz;

    root /var/www/biznespilot/public;
    index index.php;
    charset utf-8;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Permissions-Policy "camera=(), microphone=(), geolocation=()" always;

    access_log /var/log/nginx/biznespilot-access.log;
    error_log /var/log/nginx/biznespilot-error.log;

    # Laravel
    location / {
        try_files $uri $uri/ /index.php?$query_string;
        limit_req zone=general burst=20 nodelay;
    }

    # PHP-FPM
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php-fpm-biznespilot.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;

        fastcgi_connect_timeout 60;
        fastcgi_send_timeout 180;
        fastcgi_read_timeout 180;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
    }

    # API
    location /api {
        try_files $uri $uri/ /index.php?$query_string;
        limit_req zone=api burst=50 nodelay;
    }

    # Login brute-force
    location /login {
        try_files $uri $uri/ /index.php?$query_string;
        limit_req zone=login burst=3 nodelay;
    }

    # Vite build (1 yil cache)
    location /build/ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # Static files (30 kun cache)
    location ~* \.(jpg|jpeg|png|gif|ico|webp|avif|css|js|pdf|woff|woff2|ttf|svg|eot)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # Hidden files bloklash
    location ~ /\.(?!well-known).* {
        deny all;
    }

    location ~ /\.env {
        deny all;
    }

    location ~ /vendor {
        deny all;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;
}
EOF
```

```bash
ln -sf /etc/nginx/sites-available/biznespilot /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl restart nginx
```

---

## 14. Firewall (UFW)

```bash
ufw default deny incoming
ufw default allow outgoing
ufw allow 2222/tcp comment 'SSH'
ufw allow http comment 'HTTP'
ufw allow https comment 'HTTPS'
ufw --force enable
ufw status verbose
```

---

## 15. Fail2ban

```bash
cat > /etc/fail2ban/jail.local << 'EOF'
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 5
backend = systemd
banaction = ufw

[sshd]
enabled = true
port = 2222
maxretry = 3
bantime = 86400

[nginx-http-auth]
enabled = true

[nginx-limit-req]
enabled = true
logpath = /var/log/nginx/biznespilot-error.log
maxretry = 10
findtime = 60
bantime = 600

[nginx-botsearch]
enabled = true
logpath = /var/log/nginx/biznespilot-access.log
maxretry = 5
EOF
```

```bash
systemctl restart fail2ban
systemctl enable fail2ban
fail2ban-client status
```

---

## 16. Log rotation

```bash
cat > /etc/logrotate.d/biznespilot << 'EOF'
/var/www/biznespilot/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0664 deploy www-data
    sharedscripts
    postrotate
        /usr/bin/supervisorctl restart biznespilot-worker:* > /dev/null 2>&1 || true
    endscript
}

/var/log/php-fpm/biznespilot-*.log {
    weekly
    rotate 4
    compress
    missingok
    notifempty
    create 0640 www-data adm
}

/var/log/nginx/biznespilot-*.log {
    daily
    rotate 14
    compress
    delaycompress
    missingok
    notifempty
    create 0640 www-data adm
    sharedscripts
    postrotate
        [ -f /var/run/nginx.pid ] && kill -USR1 $(cat /var/run/nginx.pid)
    endscript
}
EOF
```

---

## 17. Loyiha papkasini tayyorlash

```bash
mkdir -p /var/www/biznespilot
mkdir -p /var/www/biznespilot/storage/logs
mkdir -p /var/www/biznespilot/storage/framework/{sessions,views,cache}
mkdir -p /var/www/biznespilot/storage/app/public
mkdir -p /var/www/biznespilot/bootstrap/cache
chown -R deploy:www-data /var/www/biznespilot
chmod -R 775 /var/www/biznespilot
```

---

## 18. GitHub SSH key (deploy user)

Deploy user ga o'tish:

```bash
su - deploy
```

SSH key yaratish:

```bash
ssh-keygen -t ed25519 -C "deploy@biznespilot-server"
```

Public key ni ko'rish:

```bash
cat ~/.ssh/id_ed25519.pub
```

Bu keyni nusxalang va:

1. GitHub → `umdsoft/biznespilot` repository → **Settings** → **Deploy Keys**
2. **Add deploy key** bosing
3. Title: `biznespilot-server`
4. Key: nusxalangan keyni paste qiling
5. **Allow write access** — belgilamang
6. **Add key** bosing

Ulanishni tekshirish:

```bash
ssh -T git@github.com
```

Javob: `Hi umdsoft! You've successfully authenticated...`

Git config:

```bash
git config --global user.name "BiznesPilot Deploy"
git config --global user.email "deploy@biznespilot.uz"
git config --global pull.rebase false
```

---

## 19. Repository klonlash

> Hali deploy user sifatida davom eting (`su - deploy` qilgan edingiz).

```bash
cd /var/www
git clone git@github.com:umdsoft/biznespilot.git biznespilot
cd biznespilot
```

Permissions:

```bash
sudo chown -R deploy:www-data /var/www/biznespilot
sudo chmod -R 775 /var/www/biznespilot/storage
sudo chmod -R 775 /var/www/biznespilot/bootstrap/cache
```

---

## 20. Composer install

```bash
cd /var/www/biznespilot
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
```

> 1-3 daqiqa olishi mumkin. Swap bo'lgani uchun OOM bo'lmaydi.

---

## 21. .env sozlash

```bash
cp .env.production .env
nano .env
```

> `.env.production` fayli tayyor template bilan keladi. Faqat quyidagilarni to'ldiring:

**ALBATTA o'zgartiring:**

```
APP_KEY=                              ← keyingi qadamda generatsiya qilinadi
DB_PASSWORD=SIZ_11_QADAMDA_YARATGAN_PAROL
```

**Tekshiring (to'g'ri ekanligiga ishonch hosil qiling):**

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://biznespilot.uz
TELESCOPE_ENABLED=false
SESSION_SECURE_COOKIE=true
QUEUE_CONNECTION=redis
CACHE_STORE=redis
```

**Ixtiyoriy (keyinroq ham qo'shsa bo'ladi):**

```
META_APP_SECRET=...
ANTHROPIC_API_KEY=...
LOG_TELEGRAM_BOT_TOKEN=...
LOG_TELEGRAM_CHAT_ID=...
PAYME_MERCHANT_ID=...
CLICK_MERCHANT_ID=...
```

Saqlang va chiqing (Ctrl+O, Enter, Ctrl+X).

---

## 22. App key generatsiya

```bash
php artisan key:generate
```

---

## 23. Database migration

```bash
php artisan migrate --force
```

---

## 24. Storage link

```bash
php artisan storage:link
```

---

## 25. Cache yaratish

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

---

## 26. Supervisor sozlash (Queue workers + Scheduler)

> Bu root sifatida bajarish kerak. Agar deploy user da bo'lsangiz: `exit` qilib root ga qayting.

```bash
exit
```

Root sifatida:

```bash
cat > /etc/supervisor/conf.d/biznespilot.conf << 'EOF'
[program:biznespilot-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/biznespilot/artisan queue:work redis --sleep=3 --tries=3 --max-time=300 --memory=128 --max-jobs=100
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=deploy
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/biznespilot/storage/logs/worker.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=3
stopwaitsecs=30

[program:biznespilot-scheduler]
process_name=%(program_name)s
command=/bin/sh -c "while [ true ]; do (php /var/www/biznespilot/artisan schedule:run --verbose --no-interaction &); sleep 60; done"
autostart=true
autorestart=true
user=deploy
redirect_stderr=true
stdout_logfile=/var/www/biznespilot/storage/logs/scheduler.log
stdout_logfile_maxbytes=5MB
stdout_logfile_backups=2
EOF
```

```bash
supervisorctl reread
supervisorctl update
supervisorctl status
```

Natija:

```
biznespilot-worker:biznespilot-worker_00   RUNNING
biznespilot-worker:biznespilot-worker_01   RUNNING
biznespilot-scheduler                       RUNNING
```

---

## 27. Servislarni restart

```bash
systemctl restart php8.2-fpm
systemctl restart nginx
supervisorctl restart all
```

---

## 28. Birinchi tekshirish

```bash
curl -s -o /dev/null -w "HTTP Status: %{http_code}\n" http://localhost
```

Javob `HTTP Status: 200` yoki `302` bo'lishi kerak.

Agar `502` — PHP-FPM tekshiring:

```bash
systemctl status php8.2-fpm
ls -la /run/php/php-fpm-biznespilot.sock
tail -20 /var/log/php-fpm/biznespilot-error.log
```

Agar `500` — Laravel log tekshiring:

```bash
tail -50 /var/www/biznespilot/storage/logs/laravel.log
```

---

## 29. Domain DNS sozlash

Domain provayderingizda (Reg.uz, Ahost, Cloudflare va h.k.) quyidagi DNS record'larni qo'shing:

| Turi | Nomi | Qiymati |
|------|------|---------|
| A | biznespilot.uz | SERVER_IP |
| A | www.biznespilot.uz | SERVER_IP |

DNS tarqalishi 5 daqiqadan 48 soatgacha olishi mumkin.

Tekshirish:

```bash
dig biznespilot.uz +short
```

Server IP ko'rsatishi kerak.

---

## 30. SSL sertifikat (Let's Encrypt)

> DNS tayyor bo'lgandan keyingina bajaring.

```bash
certbot --nginx -d biznespilot.uz -d www.biznespilot.uz
```

Javoblar:
- Email: admin@biznespilot.uz
- Agree: **Y**
- Share email: **N**

Certbot nginx konfiguratsiyani avtomatik yangilaydi (HTTPS redirect, sertifikat path'lari).

Auto-renewal tekshirish:

```bash
systemctl status certbot.timer
certbot renew --dry-run
```

### HSTS yoqish (SSL tayyor bo'lgandan keyin)

```bash
nano /etc/nginx/sites-available/biznespilot
```

HTTPS server blokiga qo'shing:

```
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
```

```bash
nginx -t && systemctl reload nginx
```

---

## 31. deploy.sh ni ishga tayyor qilish

```bash
su - deploy
cd /var/www/biznespilot
chmod +x deploy.sh
```

Tekshirish:

```bash
./deploy.sh status
```

---

## 32. YAKUNLANDI — xulosa

Hozir serverda ishlayotgan narsalar:

| Servis | Holat | Buyruq |
|--------|-------|--------|
| PHP 8.2-FPM | ondemand, 5 max | `systemctl status php8.2-fpm` |
| Nginx | HTTP/HTTPS | `systemctl status nginx` |
| MySQL | tuned, 50 conn | `systemctl status mysql` |
| Redis | 128mb limit | `systemctl status redis-server` |
| Supervisor | 2 worker + scheduler | `supervisorctl status` |
| UFW | SSH+HTTP+HTTPS | `ufw status` |
| Fail2ban | SSH+Nginx | `fail2ban-client status` |
| SSL | Auto-renew | `certbot certificates` |
| OPcache+JIT | 128mb + 64mb | PHP info |
| Swap | 2GB | `free -h` |
| Log rotation | daily/weekly | `/etc/logrotate.d/biznespilot` |

---

## Kundalik deploy

### Lokal kompyuterda (Windows):

```bash
npm run build
git add .
git commit -m "feat: yangi funksiya"
git push origin main
```

### Serverda (SSH bilan):

```bash
ssh -p 2222 deploy@SERVER_IP
cd /var/www/biznespilot
./deploy.sh
```

| Buyruq | Tavsif |
|--------|--------|
| `./deploy.sh` | Git pull + smart cache (DEFAULT) |
| `./deploy.sh full` | Composer + migrate + cache |
| `./deploy.sh rollback` | Oxirgi commitga qaytish |
| `./deploy.sh status` | Server holatini ko'rish |

---

## Troubleshooting

### 502 Bad Gateway

```bash
systemctl status php8.2-fpm
ls -la /run/php/php-fpm-biznespilot.sock
systemctl restart php8.2-fpm && systemctl restart nginx
tail -50 /var/log/php-fpm/biznespilot-error.log
```

### Permission denied

```bash
sudo chown -R deploy:www-data /var/www/biznespilot
sudo chmod -R 775 /var/www/biznespilot/storage
sudo chmod -R 775 /var/www/biznespilot/bootstrap/cache
```

### Out of Memory (OOM)

```bash
free -h
ps aux --sort=-%mem | head -10
sudo supervisorctl stop biznespilot-worker:*
# PHP-FPM max_children kamaytirib qayta restart:
sudo systemctl restart php8.2-fpm
```

### Redis connection refused

```bash
systemctl restart redis-server
redis-cli ping
```

### Queue ishlamayapti

```bash
sudo supervisorctl status
tail -100 /var/www/biznespilot/storage/logs/worker.log
cd /var/www/biznespilot && php artisan queue:failed
php artisan queue:retry all
sudo supervisorctl restart biznespilot-worker:*
```

### Git conflict

```bash
cd /var/www/biznespilot
git stash
git pull origin main
git stash pop
```

### SSL muddati tugagan

```bash
sudo certbot renew
sudo systemctl reload nginx
```

---

## Cheat sheet

```bash
# Deploy
./deploy.sh                     # Standard (git pull + cache)
./deploy.sh full                # Full (composer + migrate)
./deploy.sh rollback            # Qaytish
./deploy.sh status              # Holat

# Servislar
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx
sudo systemctl restart mysql
sudo systemctl restart redis-server

# Supervisor
sudo supervisorctl status
sudo supervisorctl restart all
sudo supervisorctl restart biznespilot-worker:*

# Laravel
cd /var/www/biznespilot
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
php artisan queue:failed
php artisan down                # Maintenance ON
php artisan up                  # Maintenance OFF

# Loglar
tail -f storage/logs/laravel.log
tail -f storage/logs/worker.log
sudo tail -f /var/log/nginx/biznespilot-error.log
sudo tail -f /var/log/php-fpm/biznespilot-error.log
sudo tail -f /var/log/mysql/slow.log

# Monitoring
free -h                         # RAM
df -h                           # Disk
htop                            # Jarayonlar
```
