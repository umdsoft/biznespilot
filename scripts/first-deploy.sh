#!/bin/bash

# =============================================================================
# BIZNESPILOT — BIRINCHI DEPLOY
# =============================================================================
# server-setup.sh ishlagandan keyin, root sifatida ishga tushiring:
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
GITHUB_REPO="git@github.com:umdsoft/biznespilot.git"
DOMAIN="biznespilot.uz"
PHP_VERSION="8.3"

# server-setup.sh yaratgan credentials fayli
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
    echo "  1. ssh-keygen -t ed25519 -C 'root@biznespilot'"
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

chown -R www-data:www-data ${DEPLOY_PATH}
chmod -R 775 ${DEPLOY_PATH}/storage ${DEPLOY_PATH}/bootstrap/cache

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

    # Production sozlamalarini o'rnatish
    sed -i 's/^APP_ENV=.*/APP_ENV=production/' ${DEPLOY_PATH}/.env
    sed -i 's/^APP_DEBUG=.*/APP_DEBUG=false/' ${DEPLOY_PATH}/.env
    sed -i "s|^APP_URL=.*|APP_URL=https://${DOMAIN}|" ${DEPLOY_PATH}/.env
    log_success "APP_ENV=production, APP_DEBUG=false, APP_URL sozlandi"

    # Database credentiallarni server-setup.sh dan o'qib .env ga yozish
    if [ -f "${CREDENTIALS_FILE}" ]; then
        log_info "Database credentiallar ${CREDENTIALS_FILE} dan o'qilmoqda..."

        DB_DATABASE=$(grep '^DB_DATABASE=' "${CREDENTIALS_FILE}" | cut -d= -f2)
        DB_USERNAME=$(grep '^DB_USERNAME=' "${CREDENTIALS_FILE}" | cut -d= -f2)
        DB_PASSWORD=$(grep '^DB_PASSWORD=' "${CREDENTIALS_FILE}" | cut -d= -f2)

        if [ -n "$DB_DATABASE" ] && [ -n "$DB_USERNAME" ] && [ -n "$DB_PASSWORD" ]; then
            sed -i "s/^DB_DATABASE=.*/DB_DATABASE=${DB_DATABASE}/" ${DEPLOY_PATH}/.env
            sed -i "s/^DB_USERNAME=.*/DB_USERNAME=${DB_USERNAME}/" ${DEPLOY_PATH}/.env
            sed -i "s/^DB_PASSWORD=.*/DB_PASSWORD=${DB_PASSWORD}/" ${DEPLOY_PATH}/.env
            sed -i 's/^DB_HOST=.*/DB_HOST=127.0.0.1/' ${DEPLOY_PATH}/.env
            log_success "Database credentiallar .env ga yozildi (DB: ${DB_DATABASE}, User: ${DB_USERNAME})"
        else
            log_warning "Credentials fayl to'liq emas — qo'lda yozing: nano ${DEPLOY_PATH}/.env"
        fi
    else
        log_warning "Credentials fayl topilmadi (${CREDENTIALS_FILE})"
        log_warning "Database ma'lumotlarini qo'lda yozing: nano ${DEPLOY_PATH}/.env"
    fi

    # Cache va Queue driverlarini Redis ga sozlash
    sed -i 's/^CACHE_STORE=.*/CACHE_STORE=redis/' ${DEPLOY_PATH}/.env
    sed -i 's/^QUEUE_CONNECTION=.*/QUEUE_CONNECTION=redis/' ${DEPLOY_PATH}/.env
    log_success "CACHE_STORE=redis, QUEUE_CONNECTION=redis sozlandi"

    # App key generatsiya
    php artisan key:generate --force
    log_success "APP_KEY generatsiya qilindi"

    echo ""
    log_warning "══════════════════════════════════════════════════════"
    log_warning "  .env faylni tekshiring va qo'shimcha sozlamalar kiriting:"
    log_warning "  nano ${DEPLOY_PATH}/.env"
    log_warning ""
    log_warning "  Qo'lda kiritish kerak bo'lgan sozlamalar:"
    log_warning "    ANTHROPIC_API_KEY=... (Claude CLI uchun)"
    log_warning "    TELEGRAM_SYSTEM_BOT_TOKEN=..."
    log_warning "    META_APP_ID=..."
    log_warning "    META_APP_SECRET=..."
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

# deploy.sh ni tayyorlash (keyingi deploy'lar uchun)
if [ -f "${DEPLOY_PATH}/deploy.sh" ]; then
    chmod +x ${DEPLOY_PATH}/deploy.sh
    log_success "deploy.sh tayyor (keyingi deploy: ./deploy.sh)"
else
    log_warning "deploy.sh topilmadi"
fi

# =============================================================================
# 8. SERVISLARNI RESTART
# =============================================================================
log_step "8/9 — SERVISLAR RESTART"

systemctl restart php${PHP_VERSION}-fpm
log_success "PHP-FPM restarted"

systemctl restart nginx
log_success "Nginx restarted"

supervisorctl reread
supervisorctl update
supervisorctl restart all
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
WORKER_STATUS=$(supervisorctl status biznespilot-worker:biznespilot-worker_00 2>/dev/null | awk '{print $2}')
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
echo "     certbot --nginx -d ${DOMAIN} -d www.${DOMAIN}"
echo ""
echo "  4. Keyingi deploy'lar uchun (xavfsiz deploy pipeline):"
echo "     cd ${DEPLOY_PATH} && ./deploy.sh          # pull (default)"
echo "     cd ${DEPLOY_PATH} && ./deploy.sh full      # composer + migrate"
echo "     cd ${DEPLOY_PATH} && ./deploy.sh rollback  # oxirgi commitga qaytish"
echo "     cd ${DEPLOY_PATH} && ./deploy.sh status    # server holati"
echo ""
echo "  5. Server holatini tekshirish:"
echo "     server-health"
echo ""
echo "  6. Claude CLI ishlatish:"
echo "     cd ${DEPLOY_PATH} && claude"
echo ""
