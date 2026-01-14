#!/bin/bash

# =============================================================================
# BIZNESPILOT - SERVER SETUP SCRIPT
# =============================================================================
# VPS 3 (Ubuntu 22.04/24.04) uchun avtomatik sozlash
# Ishlatish: curl -sSL URL | bash
# =============================================================================

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log_info() { echo -e "${BLUE}[INFO]${NC} $1"; }
log_success() { echo -e "${GREEN}[SUCCESS]${NC} $1"; }
log_warning() { echo -e "${YELLOW}[WARNING]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }

# =============================================================================
# CONFIGURATION
# =============================================================================
DEPLOY_USER="deploy"
DEPLOY_PATH="/var/www/biznespilot"
DOMAIN="biznespilot.uz"  # O'zgartiring!
PHP_VERSION="8.2"
NODE_VERSION="20"
GITHUB_REPO="https://github.com/YOUR_USERNAME/biznespilot.git"  # O'zgartiring!

# =============================================================================
# 1. SYSTEM UPDATE
# =============================================================================
log_info "Tizimni yangilash..."
apt update && apt upgrade -y

# =============================================================================
# 2. INSTALL REQUIRED PACKAGES
# =============================================================================
log_info "Kerakli paketlarni o'rnatish..."
apt install -y \
    curl \
    wget \
    git \
    unzip \
    supervisor \
    ufw \
    fail2ban \
    htop \
    ncdu

# =============================================================================
# 3. INSTALL PHP 8.2
# =============================================================================
log_info "PHP ${PHP_VERSION} o'rnatish..."
apt install -y software-properties-common
add-apt-repository -y ppa:ondrej/php
apt update

apt install -y \
    php${PHP_VERSION}-fpm \
    php${PHP_VERSION}-cli \
    php${PHP_VERSION}-mysql \
    php${PHP_VERSION}-pgsql \
    php${PHP_VERSION}-sqlite3 \
    php${PHP_VERSION}-redis \
    php${PHP_VERSION}-mbstring \
    php${PHP_VERSION}-xml \
    php${PHP_VERSION}-curl \
    php${PHP_VERSION}-zip \
    php${PHP_VERSION}-gd \
    php${PHP_VERSION}-intl \
    php${PHP_VERSION}-bcmath \
    php${PHP_VERSION}-opcache

# =============================================================================
# 4. INSTALL COMPOSER
# =============================================================================
log_info "Composer o'rnatish..."
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

# =============================================================================
# 5. INSTALL NODE.JS (faqat build uchun)
# =============================================================================
log_info "Node.js ${NODE_VERSION} o'rnatish..."
curl -fsSL https://deb.nodesource.com/setup_${NODE_VERSION}.x | bash -
apt install -y nodejs

# =============================================================================
# 6. INSTALL NGINX
# =============================================================================
log_info "Nginx o'rnatish..."
apt install -y nginx

# =============================================================================
# 7. INSTALL MYSQL
# =============================================================================
log_info "MySQL o'rnatish..."
apt install -y mysql-server

# MySQL xavfsizligini sozlash
log_warning "MySQL root parolini o'rnating..."
mysql_secure_installation

# =============================================================================
# 8. INSTALL REDIS
# =============================================================================
log_info "Redis o'rnatish..."
apt install -y redis-server
systemctl enable redis-server
systemctl start redis-server

# =============================================================================
# 9. CREATE DEPLOY USER
# =============================================================================
log_info "Deploy foydalanuvchi yaratish..."
if ! id "$DEPLOY_USER" &>/dev/null; then
    useradd -m -s /bin/bash $DEPLOY_USER
    usermod -aG www-data $DEPLOY_USER
    log_success "Foydalanuvchi '$DEPLOY_USER' yaratildi"
else
    log_warning "Foydalanuvchi '$DEPLOY_USER' allaqachon mavjud"
fi

# SSH directory yaratish
mkdir -p /home/$DEPLOY_USER/.ssh
touch /home/$DEPLOY_USER/.ssh/authorized_keys
chmod 700 /home/$DEPLOY_USER/.ssh
chmod 600 /home/$DEPLOY_USER/.ssh/authorized_keys
chown -R $DEPLOY_USER:$DEPLOY_USER /home/$DEPLOY_USER/.ssh

# =============================================================================
# 10. CREATE PROJECT DIRECTORY
# =============================================================================
log_info "Loyiha papkasini yaratish..."
mkdir -p $DEPLOY_PATH
chown -R $DEPLOY_USER:www-data $DEPLOY_PATH
chmod -R 775 $DEPLOY_PATH

# =============================================================================
# 11. CONFIGURE PHP-FPM
# =============================================================================
log_info "PHP-FPM sozlash..."
cat > /etc/php/${PHP_VERSION}/fpm/pool.d/biznespilot.conf << 'EOF'
[biznespilot]
user = deploy
group = www-data
listen = /run/php/php-fpm-biznespilot.sock
listen.owner = www-data
listen.group = www-data

pm = dynamic
pm.max_children = 20
pm.start_servers = 5
pm.min_spare_servers = 3
pm.max_spare_servers = 10
pm.max_requests = 1000

request_terminate_timeout = 180
request_slowlog_timeout = 10s
slowlog = /var/log/php-fpm/biznespilot-slow.log

php_admin_value[error_log] = /var/log/php-fpm/biznespilot-error.log
php_admin_flag[log_errors] = on

env[APP_ENV] = production
env[APP_DEBUG] = false
EOF

mkdir -p /var/log/php-fpm
systemctl restart php${PHP_VERSION}-fpm

# =============================================================================
# 12. CONFIGURE NGINX
# =============================================================================
log_info "Nginx sozlash..."
cat > /etc/nginx/sites-available/biznespilot << EOF
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN} www.${DOMAIN};

    root ${DEPLOY_PATH}/public;
    index index.php;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Gzip
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types text/plain text/css text/xml application/json application/javascript application/rss+xml application/atom+xml image/svg+xml;

    # Max upload
    client_max_body_size 64M;

    # Logs
    access_log /var/log/nginx/biznespilot-access.log;
    error_log /var/log/nginx/biznespilot-error.log;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php-fpm-biznespilot.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;

        fastcgi_connect_timeout 60;
        fastcgi_send_timeout 180;
        fastcgi_read_timeout 180;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
    }

    # Static files caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|pdf|txt|woff|woff2|ttf|svg|eot)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # Deny hidden files
    location ~ /\.(?!well-known).* {
        deny all;
    }

    location ~ /\.env {
        deny all;
    }
}
EOF

# Enable site
ln -sf /etc/nginx/sites-available/biznespilot /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx

# =============================================================================
# 13. CONFIGURE SUPERVISOR (Queue Workers)
# =============================================================================
log_info "Supervisor sozlash..."
cat > /etc/supervisor/conf.d/biznespilot.conf << EOF
[program:biznespilot-worker]
process_name=%(program_name)s_%(process_num)02d
command=php ${DEPLOY_PATH}/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=${DEPLOY_USER}
numprocs=4
redirect_stderr=true
stdout_logfile=${DEPLOY_PATH}/storage/logs/worker.log
stopwaitsecs=3600

[program:biznespilot-scheduler]
process_name=%(program_name)s
command=/bin/sh -c "while [ true ]; do (php ${DEPLOY_PATH}/artisan schedule:run --verbose --no-interaction &); sleep 60; done"
autostart=true
autorestart=true
user=${DEPLOY_USER}
redirect_stderr=true
stdout_logfile=${DEPLOY_PATH}/storage/logs/scheduler.log
EOF

supervisorctl reread
supervisorctl update

# =============================================================================
# 14. CONFIGURE FIREWALL
# =============================================================================
log_info "Firewall sozlash..."
ufw default deny incoming
ufw default allow outgoing
ufw allow ssh
ufw allow http
ufw allow https
ufw --force enable

# =============================================================================
# 15. CONFIGURE FAIL2BAN
# =============================================================================
log_info "Fail2ban sozlash..."
systemctl enable fail2ban
systemctl start fail2ban

# =============================================================================
# 16. CREATE DATABASE
# =============================================================================
log_info "MySQL database yaratish..."
DB_PASSWORD=$(openssl rand -base64 24)

mysql -e "CREATE DATABASE IF NOT EXISTS biznespilot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS 'biznespilot'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';"
mysql -e "GRANT ALL PRIVILEGES ON biznespilot.* TO 'biznespilot'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

log_success "Database yaratildi!"
echo ""
echo "=========================================="
echo "DATABASE CREDENTIALS (SAQLANG!):"
echo "=========================================="
echo "DB_DATABASE=biznespilot"
echo "DB_USERNAME=biznespilot"
echo "DB_PASSWORD=${DB_PASSWORD}"
echo "=========================================="
echo ""

# =============================================================================
# 17. FINAL SETUP
# =============================================================================
log_info "Yakuniy sozlamalar..."

# Create storage directories
mkdir -p $DEPLOY_PATH/storage/logs
mkdir -p $DEPLOY_PATH/storage/framework/{sessions,views,cache}
mkdir -p $DEPLOY_PATH/bootstrap/cache
chown -R $DEPLOY_USER:www-data $DEPLOY_PATH
chmod -R 775 $DEPLOY_PATH/storage $DEPLOY_PATH/bootstrap/cache

# =============================================================================
# SUMMARY
# =============================================================================
echo ""
echo "=========================================="
log_success "SERVER SOZLASH YAKUNLANDI!"
echo "=========================================="
echo ""
echo "Keyingi qadamlar:"
echo ""
echo "1. GitHub SSH key qo'shing:"
echo "   cat /home/${DEPLOY_USER}/.ssh/authorized_keys"
echo "   (Bu yerga GitHub Secrets dagi SSH_PRIVATE_KEY ning public qismini qo'shing)"
echo ""
echo "2. GitHub Secrets da quyidagilarni qo'shing:"
echo "   SERVER_HOST=$(curl -s ifconfig.me)"
echo "   SERVER_USER=${DEPLOY_USER}"
echo "   DEPLOY_PATH=${DEPLOY_PATH}"
echo "   HEALTH_URL=http://$(curl -s ifconfig.me)"
echo ""
echo "3. SSL sertifikat o'rnatish (domain sozlagandan keyin):"
echo "   apt install certbot python3-certbot-nginx"
echo "   certbot --nginx -d ${DOMAIN} -d www.${DOMAIN}"
echo ""
echo "4. .env faylni yarating:"
echo "   nano ${DEPLOY_PATH}/.env"
echo ""
echo "5. Birinchi deploy uchun git push main qiling"
echo ""
echo "=========================================="
