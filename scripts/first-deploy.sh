#!/bin/bash

# =============================================================================
# BIZNESPILOT — BIRINCHI DEPLOY
# =============================================================================
# server-setup.sh ishlagandan keyin, deploy user sifatida ishga tushiring:
#   su - deploy
#   bash /var/www/biznespilot/scripts/first-deploy.sh
#
# YOKI git clone qilib bo'lgandan keyin:
#   cd /var/www/biznespilot && bash scripts/first-deploy.sh
# =============================================================================

set -euo pipefail

# =============================================================================
# CONFIGURATION
# =============================================================================
DEPLOY_PATH="/var/www/biznespilot"
DEPLOY_USER="deploy"
GITHUB_REPO="git@github.com:umdsoft/biznespilot.git"
DOMAIN="biznespilot.uz"
PHP_VERSION="8.2"

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
log_step()    { echo -e "\n${CYAN}━━━ $1 ━━━${NC}"; }

echo -e "${GREEN}"
echo "  ╔══════════════════════════════════════════╗"
echo "  ║     BIZNESPILOT — BIRINCHI DEPLOY        ║"
echo "  ╚══════════════════════════════════════════╝"
echo -e "${NC}"

# =============================================================================
# PRE-CHECKS
# =============================================================================
log_step "0/9 — TEKSHIRUVLAR"

# GitHub SSH tekshirish
log_info "GitHub SSH ulanishini tekshirish..."
if ssh -T git@github.com 2>&1 | grep -q "successfully authenticated"; then
    log_success "GitHub SSH ishlayapti"
else
    log_error "GitHub SSH ishlamayapti!"
    echo ""
    echo "  Tuzatish uchun:"
    echo "  1. ssh-keygen -t ed25519 -C 'deploy@biznespilot'"
    echo "  2. cat ~/.ssh/id_ed25519.pub"
    echo "  3. GitHub → Repo → Settings → Deploy Keys ga qo'shing"
    echo ""
    exit 1
fi

# RAM tekshirish
AVAIL_MB=$(free -m | awk 'NR==2{print $7}')
log_info "Mavjud RAM: ${AVAIL_MB}MB"
if [ "$AVAIL_MB" -lt 150 ]; then
    log_warning "RAM kam (${AVAIL_MB}MB). Composer install sekin bo'lishi mumkin."
fi

# =============================================================================
# 1. CLONE / FETCH REPOSITORY
# =============================================================================
log_step "1/9 — REPOSITORY"

if [ -d "${DEPLOY_PATH}/.git" ]; then
    log_info "Repository allaqachon mavjud — yangilanmoqda..."
    cd ${DEPLOY_PATH}
    git fetch origin main
    git checkout main
    git reset --hard origin/main
    log_success "Repository yangilandi"
else
    log_info "Repository klonlanmoqda..."
    cd /var/www

    # Agar papka bo'sh emas bo'lsa (server-setup yaratgan)
    if [ -d "${DEPLOY_PATH}" ] && [ "$(ls -A ${DEPLOY_PATH} 2>/dev/null)" ]; then
        # Temp ga clone qilib, keyin ko'chirish
        rm -rf /tmp/biznespilot-clone
        git clone ${GITHUB_REPO} /tmp/biznespilot-clone
        cp -a /tmp/biznespilot-clone/. ${DEPLOY_PATH}/
        rm -rf /tmp/biznespilot-clone
    else
        git clone ${GITHUB_REPO} ${DEPLOY_PATH}
    fi

    cd ${DEPLOY_PATH}
    log_success "Repository klonlandi"
fi

# =============================================================================
# 2. PERMISSIONS
# =============================================================================
log_step "2/9 — RUXSATLAR"

sudo chown -R ${DEPLOY_USER}:www-data ${DEPLOY_PATH}
sudo chmod -R 775 ${DEPLOY_PATH}/storage ${DEPLOY_PATH}/bootstrap/cache

# storage papkalar mavjudligini tekshirish
mkdir -p storage/logs
mkdir -p storage/framework/{sessions,views,cache}
mkdir -p storage/app/public
mkdir -p bootstrap/cache

log_success "Ruxsatlar sozlandi"

# =============================================================================
# 3. COMPOSER INSTALL
# =============================================================================
log_step "3/9 — COMPOSER DEPENDENCIES"

cd ${DEPLOY_PATH}
log_info "Composer install boshlandi (bu 1-3 daqiqa olishi mumkin)..."

composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --prefer-dist \
    --no-progress

log_success "Composer dependencies o'rnatildi"

# =============================================================================
# 4. .ENV FAYL
# =============================================================================
log_step "4/9 — ENVIRONMENT (.env)"

if [ ! -f "${DEPLOY_PATH}/.env" ]; then
    if [ -f "${DEPLOY_PATH}/.env.production" ]; then
        cp ${DEPLOY_PATH}/.env.production ${DEPLOY_PATH}/.env
        log_info ".env.production dan nusxa olindi"
    else
        cp ${DEPLOY_PATH}/.env.example ${DEPLOY_PATH}/.env
        log_info ".env.example dan nusxa olindi"
    fi

    # App key generatsiya
    php artisan key:generate --force
    log_success "APP_KEY generatsiya qilindi"

    echo ""
    log_warning "══════════════════════════════════════════════════════"
    log_warning "  .env faylni ALBATTA tahrirlang!"
    log_warning "  nano ${DEPLOY_PATH}/.env"
    log_warning ""
    log_warning "  Eng muhim sozlamalar:"
    log_warning "    APP_ENV=production"
    log_warning "    APP_DEBUG=false"
    log_warning "    APP_URL=https://${DOMAIN}"
    log_warning "    DB_PASSWORD=server-setup dan olgan parolingiz"
    log_warning "    TELESCOPE_ENABLED=false"
    log_warning "══════════════════════════════════════════════════════"
    echo ""

    read -p "  .env ni hozir tahrirlaysizmi? (y/n): " EDIT_ENV
    if [ "$EDIT_ENV" = "y" ] || [ "$EDIT_ENV" = "Y" ]; then
        nano ${DEPLOY_PATH}/.env
    else
        log_warning ".env ni keyinroq tahrirlashni UNUTMANG!"
    fi
else
    log_success ".env allaqachon mavjud"
fi

# =============================================================================
# 5. DATABASE MIGRATION
# =============================================================================
log_step "5/9 — DATABASE MIGRATION"

cd ${DEPLOY_PATH}

# DB ulanishni tekshirish
if php artisan db:show 2>/dev/null | grep -q "biznespilot"; then
    log_success "Database ulanishi ishlayapti"
else
    log_warning "Database ulanishni tekshiring (.env da DB_* sozlamalar)"
fi

log_info "Migratsiyalar ishga tushmoqda..."
php artisan migrate --force
log_success "Migratsiyalar muvaffaqiyatli"

# =============================================================================
# 6. STORAGE LINK
# =============================================================================
log_step "6/9 — STORAGE LINK"

if [ ! -L "${DEPLOY_PATH}/public/storage" ]; then
    php artisan storage:link
    log_success "Storage link yaratildi"
else
    log_warning "Storage link allaqachon mavjud"
fi

# =============================================================================
# 7. CACHE
# =============================================================================
log_step "7/9 — CACHE YARATISH"

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

log_success "Config, route, view, event cache yaratildi"

# =============================================================================
# 8. SERVISLARNI RESTART
# =============================================================================
log_step "8/9 — SERVISLAR RESTART"

sudo systemctl restart php${PHP_VERSION}-fpm
log_success "PHP-FPM restarted"

sudo systemctl restart nginx
log_success "Nginx restarted"

sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart all
log_success "Supervisor restarted (queue workers + scheduler)"

# =============================================================================
# 9. HEALTH CHECK
# =============================================================================
log_step "9/9 — HEALTH CHECK"

sleep 3

# HTTP tekshirish
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://localhost 2>/dev/null || echo "000")
if [ "$HTTP_STATUS" = "200" ] || [ "$HTTP_STATUS" = "302" ]; then
    log_success "HTTP ${HTTP_STATUS} — Sayt ishlayapti!"
else
    log_warning "HTTP ${HTTP_STATUS} — Tekshiring!"
    log_info "Log: tail -f ${DEPLOY_PATH}/storage/logs/laravel.log"
fi

# PHP-FPM
if systemctl is-active --quiet php${PHP_VERSION}-fpm; then
    log_success "PHP-FPM ishlayapti"
else
    log_error "PHP-FPM ishlamayapti!"
fi

# Supervisor
WORKER_STATUS=$(sudo supervisorctl status biznespilot-worker:biznespilot-worker_00 2>/dev/null | awk '{print $2}')
if [ "$WORKER_STATUS" = "RUNNING" ]; then
    log_success "Queue workers ishlayapti"
else
    log_warning "Queue workers: ${WORKER_STATUS:-unknown}"
fi

# =============================================================================
# DONE
# =============================================================================
SERVER_IP=$(curl -s ifconfig.me 2>/dev/null || echo "SERVER_IP")

echo ""
echo -e "${GREEN}╔══════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║          BIRINCHI DEPLOY MUVAFFAQIYATLI YAKUNLANDI!         ║${NC}"
echo -e "${GREEN}╚══════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${CYAN}  Sayt:     http://${SERVER_IP}${NC}"
echo -e "${CYAN}  Domain:   http://${DOMAIN} (DNS sozlangandan keyin)${NC}"
echo ""
echo -e "${YELLOW}  KEYINGI QADAMLAR:${NC}"
echo ""
echo "  1. .env ni tekshiring:"
echo "     nano ${DEPLOY_PATH}/.env"
echo ""
echo "  2. Domain DNS ni serverga yo'naltiring:"
echo "     A record: ${DOMAIN} → ${SERVER_IP}"
echo "     A record: www.${DOMAIN} → ${SERVER_IP}"
echo ""
echo "  3. SSL o'rnating:"
echo "     sudo certbot --nginx -d ${DOMAIN} -d www.${DOMAIN}"
echo ""
echo "  4. Keyingi deploy'lar uchun:"
echo "     cd ${DEPLOY_PATH} && ./deploy.sh"
echo ""
echo "  5. Server holatini tekshirish:"
echo "     server-health"
echo ""
