#!/bin/bash

# =============================================================================
# BIZNESPILOT — FULL SERVER SETUP (Ubuntu 22.04/24.04)
# =============================================================================
# Yangi VPS ga root sifatida ulangandan keyin ishga tushiring:
#   bash server-setup.sh
#
# Bu script quyidagilarni o'rnatadi va sozlaydi:
#   PHP 8.2, Composer, MySQL, Redis, Nginx, Supervisor,
#   UFW, Fail2ban, Swap, OPcache, SSL, Log rotation
# =============================================================================

set -euo pipefail

# =============================================================================
# CONFIGURATION — BU YERDA O'ZGARTIRING
# =============================================================================
DEPLOY_USER="deploy"
DEPLOY_PATH="/var/www/biznespilot"
DOMAIN="biznespilot.uz"
PHP_VERSION="8.2"
SSH_PORT="2222"           # SSH portni o'zgartiring (default 22 dan)
SWAP_SIZE="2G"            # 1-2GB VPS uchun 2GB swap MAJBURIY
GITHUB_REPO="git@github.com:umdsoft/biznespilot.git"

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
log_step "1/17 — TIZIMNI YANGILASH"

export DEBIAN_FRONTEND=noninteractive
apt update && apt upgrade -y
apt autoremove -y

log_success "Tizim yangilandi"

# =============================================================================
# 2. INSTALL BASE PACKAGES
# =============================================================================
log_step "2/17 — BAZAVIY PAKETLAR"

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
log_step "3/17 — SWAP (${SWAP_SIZE})"

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
# 4. DEPLOY USER YARATISH
# =============================================================================
log_step "4/17 — DEPLOY USER"

if ! id "$DEPLOY_USER" &>/dev/null; then
    adduser --disabled-password --gecos "" $DEPLOY_USER
    usermod -aG sudo $DEPLOY_USER
    usermod -aG www-data $DEPLOY_USER

    # sudo parolsiz (deploy skriptlari uchun)
    echo "${DEPLOY_USER} ALL=(ALL) NOPASSWD: /usr/sbin/service, /bin/systemctl, /usr/bin/supervisorctl" > /etc/sudoers.d/${DEPLOY_USER}
    chmod 440 /etc/sudoers.d/${DEPLOY_USER}

    log_success "User '${DEPLOY_USER}' yaratildi"
else
    log_warning "User '${DEPLOY_USER}' allaqachon mavjud"
fi

# SSH katalog
mkdir -p /home/${DEPLOY_USER}/.ssh
touch /home/${DEPLOY_USER}/.ssh/authorized_keys
chmod 700 /home/${DEPLOY_USER}/.ssh
chmod 600 /home/${DEPLOY_USER}/.ssh/authorized_keys
chown -R ${DEPLOY_USER}:${DEPLOY_USER} /home/${DEPLOY_USER}/.ssh

# =============================================================================
# 5. SSH XAVFSIZLIK
# =============================================================================
log_step "5/17 — SSH XAVFSIZLIK"

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
AllowUsers root ${DEPLOY_USER}
EOF

log_warning "SSH port: ${SSH_PORT} ga o'zgartirildi"
log_warning "MUHIM: Yangi terminalda '${SSH_PORT}' port bilan kirishni tekshiring!"
log_warning "  ssh -p ${SSH_PORT} root@SERVER_IP"
log_info "Keyinchalik PasswordAuthentication no qiling (SSH key o'rnatgandan keyin)"

systemctl restart sshd

log_success "SSH xavfsizlik sozlandi"

# =============================================================================
# 6. PHP 8.2
# =============================================================================
log_step "6/17 — PHP ${PHP_VERSION}"

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
# 7. PHP OPCACHE + JIT
# =============================================================================
log_step "7/17 — PHP OPCACHE + JIT"

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
# 8. PHP-FPM POOL (Low-Memory Optimized)
# =============================================================================
log_step "8/17 — PHP-FPM POOL"

# Default pool ni o'chirish
if [ -f /etc/php/${PHP_VERSION}/fpm/pool.d/www.conf ]; then
    mv /etc/php/${PHP_VERSION}/fpm/pool.d/www.conf /etc/php/${PHP_VERSION}/fpm/pool.d/www.conf.disabled
fi

cat > /etc/php/${PHP_VERSION}/fpm/pool.d/biznespilot.conf << 'FPMEOF'
[biznespilot]
user = deploy
group = www-data

listen = /run/php/php-fpm-biznespilot.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660

; ==================================================
; MEMORY-SAFE: 1-2GB VPS uchun OPTIMALLASHTIRILGAN
; ==================================================
; ondemand = faqat kerak bo'lganda process ochadi
pm = ondemand
pm.max_children = 5
pm.process_idle_timeout = 20s
pm.max_requests = 500

; Timeouts
request_terminate_timeout = 180
request_slowlog_timeout = 10s
slowlog = /var/log/php-fpm/biznespilot-slow.log

; Logging
php_admin_value[error_log] = /var/log/php-fpm/biznespilot-error.log
php_admin_flag[log_errors] = on

; Security
php_admin_value[expose_php] = Off
php_admin_value[display_errors] = Off
php_admin_value[display_startup_errors] = Off
php_admin_value[open_basedir] = /var/www/biznespilot:/tmp:/var/lib/php/sessions

; Environment
env[APP_ENV] = production
env[APP_DEBUG] = false
FPMEOF

mkdir -p /var/log/php-fpm
systemctl restart php${PHP_VERSION}-fpm
systemctl enable php${PHP_VERSION}-fpm

log_success "PHP-FPM pool sozlandi (ondemand, max_children=5)"

# =============================================================================
# 9. COMPOSER
# =============================================================================
log_step "9/17 — COMPOSER"

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
# 10. MYSQL
# =============================================================================
log_step "10/17 — MYSQL"

apt install -y mysql-server
systemctl enable mysql
systemctl start mysql

# Avtomatik xavfsiz sozlash (interactive emas)
DB_PASSWORD=$(openssl rand -base64 24 | tr -d '/+=')

mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '${DB_PASSWORD}_root';" 2>/dev/null || true
mysql -e "DELETE FROM mysql.user WHERE User='';" 2>/dev/null || true
mysql -e "DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');" 2>/dev/null || true
mysql -e "DROP DATABASE IF EXISTS test;" 2>/dev/null || true
mysql -e "FLUSH PRIVILEGES;" 2>/dev/null || true

# Database va user yaratish
mysql -e "CREATE DATABASE IF NOT EXISTS biznespilot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS 'biznespilot'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';"
mysql -e "GRANT ALL PRIVILEGES ON biznespilot.* TO 'biznespilot'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

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
# 11. REDIS
# =============================================================================
log_step "11/17 — REDIS"

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
# 12. NGINX
# =============================================================================
log_step "12/17 — NGINX"

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
# HTTP → HTTPS redirect (SSL o'rnatilgandan keyin ishlaydi)
server {
    listen 80;
    listen [::]:80;
    server_name biznespilot.uz www.biznespilot.uz;

    # SSL o'rnatilgunga qadar to'g'ridan-to'g'ri xizmat qiladi
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
# 13. SUPERVISOR (Queue Workers + Scheduler)
# =============================================================================
log_step "13/17 — SUPERVISOR"

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
user=deploy
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
user=deploy
redirect_stderr=true
stdout_logfile=/var/www/biznespilot/storage/logs/scheduler.log
stdout_logfile_maxbytes=5MB
stdout_logfile_backups=2
SUPEOF

systemctl enable supervisor

log_success "Supervisor sozlandi (2 worker + scheduler)"

# =============================================================================
# 14. FIREWALL (UFW)
# =============================================================================
log_step "14/17 — FIREWALL"

ufw default deny incoming
ufw default allow outgoing
ufw allow ${SSH_PORT}/tcp comment 'SSH'
ufw allow http comment 'HTTP'
ufw allow https comment 'HTTPS'
ufw --force enable

log_success "UFW yoqildi (SSH:${SSH_PORT}, HTTP, HTTPS)"
ufw status

# =============================================================================
# 15. FAIL2BAN
# =============================================================================
log_step "15/17 — FAIL2BAN"

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
# 16. LOG ROTATION
# =============================================================================
log_step "16/17 — LOG ROTATION"

cat > /etc/logrotate.d/biznespilot << 'LREOF'
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
LREOF

log_success "Log rotation sozlandi"

# =============================================================================
# 17. LOYIHA PAPKASI + HEALTH CHECK SCRIPT
# =============================================================================
log_step "17/17 — YAKUNIY SOZLAMALAR"

# Loyiha papkasi
mkdir -p ${DEPLOY_PATH}/storage/logs
mkdir -p ${DEPLOY_PATH}/storage/framework/{sessions,views,cache}
mkdir -p ${DEPLOY_PATH}/bootstrap/cache
chown -R ${DEPLOY_USER}:www-data ${DEPLOY_PATH}
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
for svc in php8.2-fpm nginx mysql redis-server supervisor; do
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
echo -e "${YELLOW}║  DB_DATABASE = biznespilot                                  ║${NC}"
echo -e "${YELLOW}║  DB_USERNAME = biznespilot                                  ║${NC}"
echo -e "${YELLOW}║  DB_PASSWORD = ${DB_PASSWORD}               ║${NC}"
echo -e "${YELLOW}║  ROOT_PASS   = ${DB_PASSWORD}_root          ║${NC}"
echo -e "${YELLOW}╚══════════════════════════════════════════════════════════════╝${NC}"

echo ""
echo -e "${CYAN}KEYINGI QADAMLAR:${NC}"
echo ""
echo "  1. SSH key qo'shish (lokal kompyuterdan):"
echo "     ssh-copy-id -p ${SSH_PORT} deploy@$(curl -s ifconfig.me 2>/dev/null || echo 'SERVER_IP')"
echo ""
echo "  2. GitHub SSH key (serverda deploy user sifatida):"
echo "     su - deploy"
echo "     ssh-keygen -t ed25519 -C 'deploy@biznespilot'"
echo "     cat ~/.ssh/id_ed25519.pub"
echo "     # Bu keyni GitHub → Repo → Settings → Deploy Keys ga qo'shing"
echo ""
echo "  3. Birinchi deploy:"
echo "     su - deploy"
echo "     bash ${DEPLOY_PATH}/scripts/first-deploy.sh"
echo "     # Yoki qo'lda: git clone ... && composer install && ..."
echo ""
echo "  4. .env sozlash:"
echo "     nano ${DEPLOY_PATH}/.env"
echo ""
echo "  5. SSL o'rnatish (domain DNS sozlangandan keyin):"
echo "     certbot --nginx -d ${DOMAIN} -d www.${DOMAIN}"
echo ""
echo "  6. Server holatini tekshirish:"
echo "     server-health"
echo ""
