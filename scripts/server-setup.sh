#!/bin/bash

# =============================================================================
# BIZNESPILOT — FULL SERVER SETUP (Ubuntu 22.04/24.04)
# =============================================================================
# Yangi VPS ga root sifatida ulangandan keyin ishga tushiring:
#   bash server-setup.sh
#
# Bu script quyidagilarni o'rnatadi va sozlaydi:
#   PHP 8.3, Composer, Node.js LTS, Claude CLI, MySQL, Redis,
#   Nginx, Supervisor, UFW, Fail2ban, Swap, OPcache, SSL, Log rotation
#
# Hozircha root da ishlaydi. Keyinchalik deploy user ochish mumkin.
# =============================================================================

set -euo pipefail

# =============================================================================
# CONFIGURATION — BU YERDA O'ZGARTIRING
# =============================================================================
DEPLOY_PATH="/var/www/biznespilot"
DOMAIN="biznespilot.uz"
PHP_VERSION="8.3"
SSH_PORT="2222"           # SSH portni o'zgartiring (default 22 dan)
SWAP_SIZE="2G"            # 1-2GB VPS uchun 2GB swap MAJBURIY
GITHUB_REPO="git@github.com:umdsoft/biznespilot.git"

# Database sozlamalari
DB_NAME="biznespilot"
DB_USERNAME="biznespilot"
# DB_PASSWORD avtomatik generatsiya qilinadi (MySQL step da)

# Credentials fayli — first-deploy.sh bu fayldan o'qib .env ga yozadi
CREDENTIALS_FILE="/root/.biznespilot-credentials"

# =============================================================================
# COLORS & HELPERS
# =============================================================================
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

log_info()    { echo -e "${BLUE}[INFO]${NC} $1"; }
log_success() { echo -e "${GREEN}[ OK ]${NC} $1"; }
log_warning() { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_error()   { echo -e "${RED}[FAIL]${NC} $1"; }
log_step()    { echo -e "\n${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"; echo -e "${CYAN}  $1${NC}"; echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"; }

check_root() {
    if [ "$(id -u)" -ne 0 ]; then
        log_error "Bu skriptni root sifatida ishga tushiring!"
        log_error "Ishlatish: sudo bash server-setup.sh"
        exit 1
    fi
}

# Vaqtni saqlash (oxirida ko'rsatish uchun)
START_TIME=$(date +%s)

check_root

echo -e "${GREEN}"
echo "  ╔══════════════════════════════════════════╗"
echo "  ║     BIZNESPILOT — SERVER SETUP           ║"
echo "  ║     Ubuntu 22.04/24.04 LTS               ║"
echo "  ╚══════════════════════════════════════════╝"
echo -e "${NC}"

# =============================================================================
# 1. SYSTEM UPDATE
# =============================================================================
log_step "1/18 — TIZIMNI YANGILASH"

export DEBIAN_FRONTEND=noninteractive
apt update && apt upgrade -y
apt autoremove -y

log_success "Tizim yangilandi"

# =============================================================================
# 2. INSTALL BASE PACKAGES
# =============================================================================
log_step "2/18 — BAZAVIY PAKETLAR"

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

# Timezone
timedatectl set-timezone Asia/Tashkent
log_success "Timezone: Asia/Tashkent"

log_success "Bazaviy paketlar o'rnatildi"

# =============================================================================
# 3. SWAP YARATISH (1-2GB VPS UCHUN MAJBURIY)
# =============================================================================
log_step "3/18 — SWAP (${SWAP_SIZE})"

if [ -f /swapfile ]; then
    log_warning "Swap allaqachon mavjud:"
    swapon --show
else
    fallocate -l ${SWAP_SIZE} /swapfile
    chmod 600 /swapfile
    mkswap /swapfile
    swapon /swapfile

    # Qayta yuklashda ham ishlashi uchun
    if ! grep -q '/swapfile' /etc/fstab; then
        echo '/swapfile none swap sw 0 0' >> /etc/fstab
    fi

    # Swappiness optimallash
    if ! grep -q 'vm.swappiness' /etc/sysctl.conf; then
        echo 'vm.swappiness=10' >> /etc/sysctl.conf
        echo 'vm.vfs_cache_pressure=50' >> /etc/sysctl.conf
        sysctl -p
    fi

    log_success "Swap yaratildi: ${SWAP_SIZE}"
fi

free -h | head -3

# =============================================================================
# 4. SSH XAVFSIZLIK
# =============================================================================
log_step "4/18 — SSH XAVFSIZLIK"

# SSH config backup
cp /etc/ssh/sshd_config /etc/ssh/sshd_config.bak

cat > /etc/ssh/sshd_config.d/biznespilot.conf << EOF
# BiznesPilot SSH Hardening
Port ${SSH_PORT}
PermitRootLogin prohibit-password
PasswordAuthentication yes
PubkeyAuthentication yes
MaxAuthTries 5
ClientAliveInterval 300
ClientAliveCountMax 2
X11Forwarding no
EOF

log_warning "SSH port: ${SSH_PORT} ga o'zgartirildi"
log_warning "MUHIM: Yangi terminalda '${SSH_PORT}' port bilan kirishni tekshiring!"
log_warning "  ssh -p ${SSH_PORT} root@SERVER_IP"
log_info "Keyinchalik PasswordAuthentication no qiling (SSH key o'rnatgandan keyin)"

systemctl restart ssh

log_success "SSH xavfsizlik sozlandi"

# =============================================================================
# 5. PHP 8.3
# =============================================================================
log_step "5/18 — PHP ${PHP_VERSION}"

add-apt-repository -y ppa:ondrej/php
apt update

apt install -y \
    php${PHP_VERSION}-fpm \
    php${PHP_VERSION}-cli \
    php${PHP_VERSION}-mysql \
    php${PHP_VERSION}-redis \
    php${PHP_VERSION}-mbstring \
    php${PHP_VERSION}-xml \
    php${PHP_VERSION}-curl \
    php${PHP_VERSION}-zip \
    php${PHP_VERSION}-gd \
    php${PHP_VERSION}-intl \
    php${PHP_VERSION}-bcmath \
    php${PHP_VERSION}-opcache \
    php${PHP_VERSION}-readline \
    php${PHP_VERSION}-tokenizer

# PHP CLI sozlash
sed -i 's/memory_limit = .*/memory_limit = 256M/' /etc/php/${PHP_VERSION}/cli/php.ini
sed -i 's/max_execution_time = .*/max_execution_time = 300/' /etc/php/${PHP_VERSION}/cli/php.ini

# PHP FPM sozlash
sed -i 's/memory_limit = .*/memory_limit = 128M/' /etc/php/${PHP_VERSION}/fpm/php.ini
sed -i 's/max_execution_time = .*/max_execution_time = 180/' /etc/php/${PHP_VERSION}/fpm/php.ini
sed -i 's/upload_max_filesize = .*/upload_max_filesize = 64M/' /etc/php/${PHP_VERSION}/fpm/php.ini
sed -i 's/post_max_size = .*/post_max_size = 64M/' /etc/php/${PHP_VERSION}/fpm/php.ini
sed -i 's/expose_php = .*/expose_php = Off/' /etc/php/${PHP_VERSION}/fpm/php.ini
sed -i "s|;date.timezone =|date.timezone = Asia/Tashkent|" /etc/php/${PHP_VERSION}/fpm/php.ini

log_success "PHP ${PHP_VERSION} o'rnatildi"
php -v | head -1

# =============================================================================
# 6. PHP OPCACHE + JIT
# =============================================================================
log_step "6/18 — PHP OPCACHE + JIT"

cat > /etc/php/${PHP_VERSION}/mods-available/opcache-custom.ini << 'EOF'
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

cp /etc/php/${PHP_VERSION}/mods-available/opcache-custom.ini \
   /etc/php/${PHP_VERSION}/fpm/conf.d/99-opcache-custom.ini

log_success "OPcache + JIT sozlandi"

# =============================================================================
# 7. PHP-FPM POOL (Low-Memory Optimized)
# =============================================================================
log_step "7/18 — PHP-FPM POOL"

# Log papkasini oldindan yaratish
mkdir -p /var/log/php-fpm
chown www-data:www-data /var/log/php-fpm

# Default pool ni o'chirish
if [ -f /etc/php/${PHP_VERSION}/fpm/pool.d/www.conf ]; then
    mv /etc/php/${PHP_VERSION}/fpm/pool.d/www.conf /etc/php/${PHP_VERSION}/fpm/pool.d/www.conf.disabled
fi

cat > /etc/php/${PHP_VERSION}/fpm/pool.d/biznespilot.conf << 'FPMEOF'
[biznespilot]
user = www-data
group = www-data

listen = /run/php/php-fpm-biznespilot.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660

; ==================================================
; MEMORY-SAFE: 1-2GB VPS uchun OPTIMALLASHTIRILGAN
; ==================================================
pm = ondemand
pm.max_children = 5
pm.process_idle_timeout = 20s
pm.max_requests = 500

; Timeouts
request_terminate_timeout = 180

; Logging
php_admin_flag[log_errors] = on
php_admin_flag[display_errors] = off
FPMEOF

# Config sintaksisni tekshirish
if php-fpm${PHP_VERSION} -t 2>&1; then
    log_success "PHP-FPM config tekshiruvi o'tdi"
else
    log_error "PHP-FPM config xatosi! Tafsilotlar:"
    php-fpm${PHP_VERSION} -t 2>&1 || true
    log_info "Default pool qaytarilmoqda..."
    if [ -f /etc/php/${PHP_VERSION}/fpm/pool.d/www.conf.disabled ]; then
        mv /etc/php/${PHP_VERSION}/fpm/pool.d/www.conf.disabled /etc/php/${PHP_VERSION}/fpm/pool.d/www.conf
    fi
    rm -f /etc/php/${PHP_VERSION}/fpm/pool.d/biznespilot.conf
    systemctl restart php${PHP_VERSION}-fpm
    log_warning "Default www.conf pool bilan davom etilmoqda"
fi

systemctl restart php${PHP_VERSION}-fpm
systemctl enable php${PHP_VERSION}-fpm

if systemctl is-active --quiet php${PHP_VERSION}-fpm; then
    log_success "PHP-FPM pool sozlandi va ishlayapti (ondemand, max_children=5)"
else
    log_error "PHP-FPM ishga tushmadi! Diagnostika:"
    journalctl -xeu php${PHP_VERSION}-fpm.service --no-pager -n 20 || true
    exit 1
fi

# =============================================================================
# 8. COMPOSER
# =============================================================================
log_step "8/18 — COMPOSER"

if ! command -v composer &>/dev/null; then
    curl -sS https://getcomposer.org/installer | php
    mv composer.phar /usr/local/bin/composer
    chmod +x /usr/local/bin/composer
    log_success "Composer o'rnatildi"
else
    log_warning "Composer allaqachon mavjud"
fi

composer --version

# =============================================================================
# 9. NODE.JS LTS (Claude CLI uchun)
# =============================================================================
log_step "9/18 — NODE.JS LTS (Claude CLI uchun)"

# Node.js 22 LTS — faqat Claude CLI uchun, build serverda bajarilMAYDI
if ! command -v node &>/dev/null; then
    curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
    apt install -y nodejs
    log_success "Node.js o'rnatildi"
else
    log_warning "Node.js allaqachon mavjud"
fi

node --version
npm --version

log_success "Node.js LTS o'rnatildi (faqat Claude CLI uchun)"

# =============================================================================
# 10. CLAUDE CLI
# =============================================================================
log_step "10/18 — CLAUDE CLI"

# Claude Code CLI — AI yordamchi terminal (Node.js 18+ talab qiladi)
if ! command -v claude &>/dev/null; then
    npm install -g @anthropic-ai/claude-code
    log_success "Claude CLI o'rnatildi"
else
    log_warning "Claude CLI allaqachon mavjud — yangilanmoqda..."
    npm update -g @anthropic-ai/claude-code 2>/dev/null || true
fi

claude --version 2>/dev/null || log_warning "Claude CLI versiyasini tekshiring"

log_info "Claude CLI ishlatish uchun: ANTHROPIC_API_KEY ni .env ga qo'shing"
log_success "Claude CLI tayyor"

# =============================================================================
# 11. MYSQL
# =============================================================================
log_step "11/18 — MYSQL"

apt install -y mysql-server
systemctl enable mysql
systemctl start mysql

# Agar oldin sozlangan bo'lsa — o'tkazish
if [ -f "${CREDENTIALS_FILE}" ]; then
    log_warning "MySQL allaqachon sozlangan (${CREDENTIALS_FILE} mavjud) — o'tkazilmoqda"
    DB_PASSWORD=$(grep '^DB_PASSWORD=' "${CREDENTIALS_FILE}" | cut -d= -f2)
    DB_ROOT_PASSWORD=$(grep '^DB_ROOT_PASSWORD=' "${CREDENTIALS_FILE}" | cut -d= -f2)
else
    # Yangi parol generatsiya
    DB_PASSWORD=$(openssl rand -base64 24 | tr -d '/+=')
    DB_ROOT_PASSWORD="${DB_PASSWORD}_root"

    # Xavfsiz sozlash
    mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '${DB_ROOT_PASSWORD}';" 2>/dev/null || true
    mysql -e "DELETE FROM mysql.user WHERE User='';" 2>/dev/null || true
    mysql -e "DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');" 2>/dev/null || true
    mysql -e "DROP DATABASE IF EXISTS test;" 2>/dev/null || true
    mysql -e "FLUSH PRIVILEGES;" 2>/dev/null || true

    # Database va user yaratish (root parol bilan)
    mysql -u root -p"${DB_ROOT_PASSWORD}" -e "CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    mysql -u root -p"${DB_ROOT_PASSWORD}" -e "CREATE USER IF NOT EXISTS '${DB_USERNAME}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';"
    mysql -u root -p"${DB_ROOT_PASSWORD}" -e "GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USERNAME}'@'localhost';"
    mysql -u root -p"${DB_ROOT_PASSWORD}" -e "FLUSH PRIVILEGES;"

    # Credentiallarni faylga saqlash
    cat > "${CREDENTIALS_FILE}" << CREDEOF
DB_DATABASE=${DB_NAME}
DB_USERNAME=${DB_USERNAME}
DB_PASSWORD=${DB_PASSWORD}
DB_ROOT_PASSWORD=${DB_ROOT_PASSWORD}
CREDEOF
    chmod 600 "${CREDENTIALS_FILE}"
    log_success "Credentials saqlandi: ${CREDENTIALS_FILE}"
fi

log_success "MySQL sozlandi (DB: ${DB_NAME}, User: ${DB_USERNAME})"

# MySQL tuning (1-2GB VPS)
cat > /etc/mysql/mysql.conf.d/biznespilot.cnf << 'MYSQLEOF'
[mysqld]
# Memory Optimization (1-2GB VPS)
innodb_buffer_pool_size = 256M
innodb_log_buffer_size = 16M
innodb_log_file_size = 64M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT

# Connections
max_connections = 50
wait_timeout = 300
interactive_timeout = 300

# Buffers
tmp_table_size = 32M
max_heap_table_size = 32M
sort_buffer_size = 2M
join_buffer_size = 2M
read_rnd_buffer_size = 2M

# Slow query log
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2

# Charset
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci

# Security
local_infile = 0
skip-symbolic-links = 1
MYSQLEOF

systemctl restart mysql

log_success "MySQL o'rnatildi va sozlandi"

# =============================================================================
# 12. REDIS
# =============================================================================
log_step "12/18 — REDIS"

apt install -y redis-server

# Redis optimallash
sed -i 's/^# maxmemory .*/maxmemory 128mb/' /etc/redis/redis.conf
sed -i 's/^# maxmemory-policy .*/maxmemory-policy allkeys-lru/' /etc/redis/redis.conf

# Agar maxmemory hali qo'shilmagan bo'lsa
if ! grep -q "^maxmemory " /etc/redis/redis.conf; then
    echo "maxmemory 128mb" >> /etc/redis/redis.conf
    echo "maxmemory-policy allkeys-lru" >> /etc/redis/redis.conf
fi

systemctl restart redis-server
systemctl enable redis-server

log_success "Redis o'rnatildi (maxmemory=128mb)"
redis-cli ping

# =============================================================================
# 13. NGINX
# =============================================================================
log_step "13/18 — NGINX"

apt install -y nginx

# Nginx main config
cat > /etc/nginx/nginx.conf << 'NGXEOF'
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
    # Basic
    sendfile on;
    tcp_nopush on;
    tcp_nodelay on;
    keepalive_timeout 30;
    types_hash_max_size 2048;
    server_tokens off;
    client_max_body_size 64M;

    include /etc/nginx/mime.types;
    default_type application/octet-stream;

    # Logging
    access_log /var/log/nginx/access.log;
    error_log /var/log/nginx/error.log;

    # Gzip
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

    # Rate Limiting
    limit_req_zone $binary_remote_addr zone=general:10m rate=10r/s;
    limit_req_zone $binary_remote_addr zone=api:10m rate=30r/s;
    limit_req_zone $binary_remote_addr zone=login:10m rate=5r/m;

    # Virtual Hosts
    include /etc/nginx/conf.d/*.conf;
    include /etc/nginx/sites-enabled/*;
}
NGXEOF

# Site config
cat > /etc/nginx/sites-available/biznespilot << 'SITEEOF'
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

    # Logging
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

    # API rate limiting
    location /api {
        try_files $uri $uri/ /index.php?$query_string;
        limit_req zone=api burst=50 nodelay;
    }

    # Login brute-force protection
    location /login {
        try_files $uri $uri/ /index.php?$query_string;
        limit_req zone=login burst=3 nodelay;
    }

    # Vite build (hashed, long cache)
    location /build/ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # Static files
    location ~* \.(jpg|jpeg|png|gif|ico|webp|avif|css|js|pdf|woff|woff2|ttf|svg|eot)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # Deny hidden files
    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Deny .env
    location ~ /\.env {
        deny all;
    }

    # Deny vendor
    location ~ /vendor {
        deny all;
    }

    # Favicon/robots
    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;
}
SITEEOF

# Enable site
ln -sf /etc/nginx/sites-available/biznespilot /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

nginx -t && systemctl restart nginx
systemctl enable nginx

log_success "Nginx sozlandi"

# =============================================================================
# 14. SUPERVISOR (Queue Workers + Scheduler)
# =============================================================================
log_step "14/18 — SUPERVISOR"

cat > /etc/supervisor/conf.d/biznespilot.conf << 'SUPEOF'
; ===========================================
; QUEUE WORKERS (Redis) — 2 process, 128MB limit
; ===========================================
[program:biznespilot-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/biznespilot/artisan queue:work redis --sleep=3 --tries=3 --max-time=300 --memory=128 --max-jobs=100
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/biznespilot/storage/logs/worker.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=3
stopwaitsecs=30

; ===========================================
; SCHEDULER (runs every 60s)
; ===========================================
[program:biznespilot-scheduler]
process_name=%(program_name)s
command=/bin/sh -c "while [ true ]; do (php /var/www/biznespilot/artisan schedule:run --verbose --no-interaction &); sleep 60; done"
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/biznespilot/storage/logs/scheduler.log
stdout_logfile_maxbytes=5MB
stdout_logfile_backups=2
SUPEOF

systemctl enable supervisor

log_success "Supervisor sozlandi (2 worker + scheduler)"

# =============================================================================
# 15. FIREWALL (UFW)
# =============================================================================
log_step "15/18 — FIREWALL"

ufw default deny incoming
ufw default allow outgoing
ufw allow ${SSH_PORT}/tcp comment 'SSH'
ufw allow http comment 'HTTP'
ufw allow https comment 'HTTPS'
ufw --force enable

log_success "UFW yoqildi (SSH:${SSH_PORT}, HTTP, HTTPS)"
ufw status

# =============================================================================
# 16. FAIL2BAN
# =============================================================================
log_step "16/18 — FAIL2BAN"

cat > /etc/fail2ban/jail.local << EOF
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 5
backend = systemd
banaction = ufw

[sshd]
enabled = true
port = ${SSH_PORT}
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

systemctl restart fail2ban
systemctl enable fail2ban

log_success "Fail2ban sozlandi"

# =============================================================================
# 17. LOG ROTATION
# =============================================================================
log_step "17/18 — LOG ROTATION"

cat > /etc/logrotate.d/biznespilot << 'LREOF'
/var/www/biznespilot/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0664 www-data www-data
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
LREOF

log_success "Log rotation sozlandi"

# =============================================================================
# 18. LOYIHA PAPKASI + HEALTH CHECK SCRIPT
# =============================================================================
log_step "18/18 — YAKUNIY SOZLAMALAR"

# Loyiha papkasi
mkdir -p ${DEPLOY_PATH}/storage/logs
mkdir -p ${DEPLOY_PATH}/storage/framework/{sessions,views,cache}
mkdir -p ${DEPLOY_PATH}/bootstrap/cache
chown -R www-data:www-data ${DEPLOY_PATH}
chmod -R 775 ${DEPLOY_PATH}

# Server health check script
cat > /usr/local/bin/server-health << 'HEALTHEOF'
#!/bin/bash
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; BLUE='\033[0;34m'; NC='\033[0m'

echo ""
echo -e "${BLUE}══════════════════════════════════════${NC}"
echo -e "${BLUE}  SERVER HEALTH — $(date '+%Y-%m-%d %H:%M:%S')${NC}"
echo -e "${BLUE}══════════════════════════════════════${NC}"

# Memory
echo ""
echo -e "${YELLOW}--- MEMORY ---${NC}"
free -h | head -3

# Disk
echo ""
echo -e "${YELLOW}--- DISK ---${NC}"
df -h / | tail -1 | awk '{printf "  Used: %s / %s (%s)\n", $3, $2, $5}'

# Load
echo ""
echo -e "${YELLOW}--- LOAD ---${NC}"
uptime | awk -F'load average:' '{print "  Load:" $2}'

# Services
echo ""
echo -e "${YELLOW}--- SERVICES ---${NC}"

# PHP-FPM versiyasini avtomatik aniqlash
PHP_FPM_SVC=""
for ver in 8.4 8.3 8.2 8.1; do
    if systemctl is-active --quiet "php${ver}-fpm" 2>/dev/null; then
        PHP_FPM_SVC="php${ver}-fpm"
        break
    fi
done

for svc in ${PHP_FPM_SVC:-php8.3-fpm} nginx mysql redis-server supervisor; do
    if systemctl is-active --quiet $svc 2>/dev/null; then
        echo -e "  ${GREEN}[OK]${NC} $svc"
    else
        echo -e "  ${RED}[FAIL]${NC} $svc"
    fi
done

# HTTP
echo ""
echo -e "${YELLOW}--- HTTP ---${NC}"
HTTP=$(curl -s -o /dev/null -w "%{http_code}" http://localhost 2>/dev/null || echo "000")
if [ "$HTTP" = "200" ] || [ "$HTTP" = "302" ]; then
    echo -e "  ${GREEN}[OK]${NC} HTTP $HTTP"
else
    echo -e "  ${RED}[WARN]${NC} HTTP $HTTP"
fi

# PHP-FPM processes
echo ""
echo -e "${YELLOW}--- PHP-FPM ---${NC}"
FPM_COUNT=$(ps aux | grep 'php-fpm.*biznespilot' | grep -v grep | wc -l)
echo "  Active processes: ${FPM_COUNT}"

# Queue
echo ""
echo -e "${YELLOW}--- SUPERVISOR ---${NC}"
supervisorctl status 2>/dev/null | while read line; do echo "  $line"; done

# SSL
echo ""
echo -e "${YELLOW}--- SSL ---${NC}"
if [ -f /etc/letsencrypt/live/biznespilot.uz/fullchain.pem ]; then
    EXPIRY=$(openssl x509 -enddate -noout -in /etc/letsencrypt/live/biznespilot.uz/fullchain.pem 2>/dev/null | cut -d= -f2)
    echo -e "  ${GREEN}[OK]${NC} Expires: $EXPIRY"
else
    echo -e "  ${YELLOW}[WARN]${NC} SSL not installed"
fi

echo ""
echo -e "${BLUE}══════════════════════════════════════${NC}"
HEALTHEOF

chmod +x /usr/local/bin/server-health

log_success "Health check script: server-health"

# =============================================================================
# SUMMARY
# =============================================================================
END_TIME=$(date +%s)
ELAPSED=$(( END_TIME - START_TIME ))

echo ""
echo -e "${GREEN}╔══════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║            SERVER SOZLASH MUVAFFAQIYATLI YAKUNLANDI!        ║${NC}"
echo -e "${GREEN}║            Vaqt: ${ELAPSED} sekund                                    ║${NC}"
echo -e "${GREEN}╚══════════════════════════════════════════════════════════════╝${NC}"

echo ""
echo -e "${YELLOW}╔══════════════════════════════════════════════════════════════╗${NC}"
echo -e "${YELLOW}║  DATABASE CREDENTIALS — BU MA'LUMOTLARNI SAQLANG!          ║${NC}"
echo -e "${YELLOW}╠══════════════════════════════════════════════════════════════╣${NC}"
echo -e "${YELLOW}║  DB_DATABASE = ${DB_NAME}${NC}"
echo -e "${YELLOW}║  DB_USERNAME = ${DB_USERNAME}${NC}"
echo -e "${YELLOW}║  DB_PASSWORD = ${DB_PASSWORD}${NC}"
echo -e "${YELLOW}║  ROOT_PASS   = ${DB_ROOT_PASSWORD}${NC}"
echo -e "${YELLOW}║${NC}"
echo -e "${YELLOW}║  Credentials fayli: ${CREDENTIALS_FILE}${NC}"
echo -e "${YELLOW}║  first-deploy.sh .env ga avtomatik yozadi${NC}"
echo -e "${YELLOW}╚══════════════════════════════════════════════════════════════╝${NC}"

echo ""
echo -e "${CYAN}KEYINGI QADAMLAR:${NC}"
echo ""
echo "  1. SSH portni tekshiring (YANGI terminalda!):"
echo "     ssh -p ${SSH_PORT} root@$(curl -s ifconfig.me 2>/dev/null || echo 'SERVER_IP')"
echo ""
echo "  2. GitHub SSH key yaratish:"
echo "     ssh-keygen -t ed25519 -C 'root@biznespilot'"
echo "     cat ~/.ssh/id_ed25519.pub"
echo "     # Bu keyni GitHub → Repo → Settings → Deploy Keys ga qo'shing"
echo ""
echo "  3. Birinchi deploy (root sifatida):"
echo "     bash ${DEPLOY_PATH}/scripts/first-deploy.sh"
echo ""
echo "  4. SSL o'rnatish (domain DNS sozlangandan keyin):"
echo "     certbot --nginx -d ${DOMAIN} -d www.${DOMAIN}"
echo ""
echo "  5. Keyingi deploy'lar uchun:"
echo "     cd ${DEPLOY_PATH} && ./deploy.sh          # pull (default)"
echo "     cd ${DEPLOY_PATH} && ./deploy.sh full      # composer + migrate"
echo "     cd ${DEPLOY_PATH} && ./deploy.sh status    # server holati"
echo ""
echo "  6. Server holatini tekshirish:"
echo "     server-health"
echo ""
echo "  7. Claude CLI ishlatish:"
echo "     cd ${DEPLOY_PATH} && claude"
echo ""
