#!/bin/bash

# =============================================================================
# BIZNESPILOT - PRODUCTION DEPLOYMENT SCRIPT (SAFE DEPLOYMENT PIPELINE)
# =============================================================================
# Muammo:  git pull serverda VFS deadlock, file descriptor conflict,
#          va 502 Bad Gateway keltirib chiqargan.
# Sabab:   git pull root sifatida, PHP-FPM www-data sifatida ishlaydi.
#          storage/framework/views/ va cache/ papkalaridagi fayllar ikkalasi
#          tomonidan bir vaqtda o'qiladi/yoziladi => OS-level lock.
# Yechim:  Bu skript artisan down -> cache clear -> git pull -> chown ->
#          fpm reload ketma-ketligini qat'iy bajaradi.
# =============================================================================
# Usage: ./deploy.sh [option]
# Options:
#   pull     - Git pull + smart cache (DEFAULT, eng xavfsiz)
#   full     - Full deployment (composer + migrate + cache)
#   rollback - Rollback to previous version
#   status   - Server holatini ko'rish
# =============================================================================

set -euo pipefail

# ===================== KONFIGURATSIYA =====================
APP_DIR="/var/www/biznespilot"
WEB_USER="www-data"
WEB_GROUP="www-data"
MIN_FREE_MB=200
LOCK_FILE="/tmp/biznespilot-deploy.lock"
LOG_FILE="/var/log/biznespilot-deploy.log"
DEPLOY_BRANCH="main"

# PHP-FPM versiyasini avtomatik aniqlash
detect_php_fpm() {
    for ver in 8.4 8.3 8.2 8.1; do
        if systemctl is-active --quiet "php${ver}-fpm" 2>/dev/null; then
            echo "php${ver}-fpm"
            return
        fi
    done
    echo ""
}

PHP_FPM_SERVICE=$(detect_php_fpm)

# ===================== RANGLAR =====================
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

# ===================== FUNKSIYALAR =====================
log_info()    { echo -e "${BLUE}[INFO]${NC}  $(date '+%H:%M:%S') $1"; echo "[INFO]  $(date '+%Y-%m-%d %H:%M:%S') $1" >> "$LOG_FILE" 2>/dev/null || true; }
log_success() { echo -e "${GREEN}[OK]${NC}    $(date '+%H:%M:%S') $1"; echo "[OK]    $(date '+%Y-%m-%d %H:%M:%S') $1" >> "$LOG_FILE" 2>/dev/null || true; }
log_warning() { echo -e "${YELLOW}[WARN]${NC}  $(date '+%H:%M:%S') $1"; echo "[WARN]  $(date '+%Y-%m-%d %H:%M:%S') $1" >> "$LOG_FILE" 2>/dev/null || true; }
log_error()   { echo -e "${RED}[ERROR]${NC} $(date '+%H:%M:%S') $1"; echo "[ERROR] $(date '+%Y-%m-%d %H:%M:%S') $1" >> "$LOG_FILE" 2>/dev/null || true; }

# PATH xavfsizligi - OOM dan keyin PATH buzilishini oldini oladi
export PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin

# ===================== LOCK MEXANIZMI =====================
# Bir vaqtda ikki deploy ishlamasligini ta'minlaydi
acquire_lock() {
    if [ -f "$LOCK_FILE" ]; then
        LOCK_PID=$(cat "$LOCK_FILE" 2>/dev/null)
        if [ -n "$LOCK_PID" ] && kill -0 "$LOCK_PID" 2>/dev/null; then
            log_error "Boshqa deploy jarayoni ishlayapti (PID: $LOCK_PID). Kutib turing yoki $LOCK_FILE ni o'chiring."
            exit 1
        else
            log_warning "Eskirgan lock file topildi. Tozalanmoqda..."
            rm -f "$LOCK_FILE"
        fi
    fi
    echo $$ > "$LOCK_FILE"
    trap release_lock EXIT
}

release_lock() {
    rm -f "$LOCK_FILE"
}

# ===================== ROOT TEKSHIRUVI =====================
check_root_safety() {
    if [ "$(id -u)" -ne 0 ]; then
        log_error "Bu skript root sifatida ishga tushirilishi kerak!"
        exit 1
    fi
    log_info "Root sifatida ishlayapti. Fayl ownership: ${WEB_USER}:${WEB_GROUP}"
}

# ===================== MEMORY TEKSHIRISH =====================
check_memory() {
    AVAIL_MB=$(free -m | awk 'NR==2{print $7}')
    SWAP_MB=$(free -m | awk 'NR==3{print $2}')
    TOTAL_MB=$(free -m | awk 'NR==2{print $2}')

    log_info "RAM: ${TOTAL_MB}MB | Mavjud: ${AVAIL_MB}MB | Swap: ${SWAP_MB}MB"

    if [ "$AVAIL_MB" -lt "$MIN_FREE_MB" ]; then
        log_error "Yetarli RAM yo'q! Mavjud: ${AVAIL_MB}MB, Kerak: ${MIN_FREE_MB}MB"
        log_info "Queue workerlarni to'xtatish bilan RAM bo'shatilmoqda..."

        cd "${APP_DIR}"
        php artisan queue:restart 2>/dev/null || true
        sleep 3

        AVAIL_MB=$(free -m | awk 'NR==2{print $7}')
        if [ "$AVAIL_MB" -lt "$MIN_FREE_MB" ]; then
            log_error "RAM hali ham yetarli emas: ${AVAIL_MB}MB. Deploy to'xtatildi."
            exit 1
        fi
    fi

    if [ "$SWAP_MB" -lt 1024 ]; then
        log_warning "Swap ${SWAP_MB}MB - kam! 2GB swap yaratish tavsiya etiladi."
    fi

    log_success "Memory tekshirish o'tdi"
}

# ===================== DISK TEKSHIRISH =====================
check_disk() {
    DISK_USAGE=$(df -h "${APP_DIR}" | tail -1 | awk '{print $5}' | sed 's/%//')

    if [ "$DISK_USAGE" -gt 90 ]; then
        log_error "Disk 90% dan ko'p to'lgan! (${DISK_USAGE}%) Deploy to'xtatildi."
        exit 1
    elif [ "$DISK_USAGE" -gt 80 ]; then
        log_warning "Disk ${DISK_USAGE}% to'lgan. Tozalash tavsiya etiladi."
    fi

    log_success "Disk: ${DISK_USAGE}% ishlatilgan"
}

# ===================== OWNERSHIP VA PERMISSION FIX =====================
# Bu funksiya KRITIK - file descriptor conflictni oldini oladi
fix_permissions() {
    log_info "Ownership va permission o'rnatilmoqda..."

    # Storage va bootstrap/cache uchun ownership
    chown -R "${WEB_USER}:${WEB_GROUP}" "${APP_DIR}/storage"
    chown -R "${WEB_USER}:${WEB_GROUP}" "${APP_DIR}/bootstrap/cache"

    # Papkalar uchun 775 (www-data yozishi kerak)
    find "${APP_DIR}/storage" -type d -exec chmod 775 {} \;
    find "${APP_DIR}/bootstrap/cache" -type d -exec chmod 775 {} \;

    # Fayllar uchun 664 (www-data o'qishi/yozishi kerak)
    find "${APP_DIR}/storage" -type f -exec chmod 664 {} \;
    find "${APP_DIR}/bootstrap/cache" -type f -exec chmod 664 {} \;

    # Loyiha fayllarining umumiy ownership (storage va vendor tashqari)
    chown -R "${WEB_USER}:${WEB_GROUP}" "${APP_DIR}"

    # Yozilmaydigan fayllar uchun 644
    find "${APP_DIR}" -maxdepth 1 -type f -exec chmod 644 {} \;
    chmod 755 "${APP_DIR}/artisan"
    chmod 755 "${APP_DIR}/deploy.sh"

    log_success "Ownership va permissionlar o'rnatildi (${WEB_USER}:${WEB_GROUP})"
}

# ===================== CACHE VA FILE DESCRIPTOR TOZALASH =====================
# KRITIK: git pull dan OLDIN bajarilishi shart
# PHP-FPM ochiq tutgan file handlelarni yopish uchun
clear_caches_before_pull() {
    log_info "Cache va file descriptorlar tozalanmoqda (git pull oldidan)..."

    cd "${APP_DIR}"

    # Laravel cacheni tozalash (artisan orqali)
    php artisan view:clear 2>/dev/null || true
    php artisan config:clear 2>/dev/null || true
    php artisan route:clear 2>/dev/null || true
    php artisan event:clear 2>/dev/null || true

    # KRITIK: Jismoniy view cache fayllarini o'chirish
    # Bu PHP-FPM file descriptorlarini bo'shatadi
    if [ -d "${APP_DIR}/storage/framework/views" ]; then
        find "${APP_DIR}/storage/framework/views" -name "*.php" -delete 2>/dev/null || true
    fi

    # Session fayllarini tozalash (ixtiyoriy, faqat file driver bo'lsa)
    # find "${APP_DIR}/storage/framework/sessions" -name "*.php" -mmin +120 -delete 2>/dev/null || true

    log_success "Cacheler tozalandi"
}

# ===================== PHP-FPM RELOAD =====================
# OPcache va file descriptorlarni tozalaydi
reload_php_fpm() {
    if [ -n "$PHP_FPM_SERVICE" ]; then
        log_info "PHP-FPM reload qilinmoqda (${PHP_FPM_SERVICE})..."
        systemctl reload "$PHP_FPM_SERVICE"
        log_success "PHP-FPM reloaded (${PHP_FPM_SERVICE})"
    else
        log_warning "PHP-FPM service topilmadi! Qo'lda reload qiling."
    fi
}

# ===================== HEALTH CHECK =====================
health_check() {
    sleep 3
    HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" --max-time 10 "http://localhost" || echo "000")
    if [ "$HTTP_STATUS" = "200" ] || [ "$HTTP_STATUS" = "302" ]; then
        log_success "Health check o'tdi (HTTP ${HTTP_STATUS})"
        return 0
    else
        log_warning "Health check: HTTP ${HTTP_STATUS} - tekshiring!"
        return 1
    fi
}

# ===================== PULL DEPLOY (ASOSIY) =====================
pull_deploy() {
    echo ""
    log_info "========================================="
    log_info "  PULL DEPLOY boshlandi"
    log_info "========================================="
    echo ""

    acquire_lock
    check_root_safety
    check_memory
    check_disk

    cd "${APP_DIR}"

    # 1. MAINTENANCE MODE - yangi requestlarni to'xtatish
    php artisan down --retry=30 --refresh=5 2>/dev/null || true
    log_success "Maintenance mode ON"

    # 2. CACHE TOZALASH (git pull OLDIDAN - file descriptor conflict oldini olish)
    clear_caches_before_pull

    # 3. PHP-FPM RELOAD (file descriptorlarni yopish)
    reload_php_fpm

    # 4. GIT PULL
    log_info "Git pull..."
    git config pull.rebase false 2>/dev/null || true
    if ! git pull origin "$DEPLOY_BRANCH"; then
        log_error "Git pull xatolik! Conflict bo'lishi mumkin."
        log_info "Tuzatish: git reset --hard origin/${DEPLOY_BRANCH}"
        php artisan up 2>/dev/null || true
        exit 1
    fi
    log_success "Git pull muvaffaqiyatli"

    # 5. COMPOSER (faqat kerak bo'lsa)
    if git diff HEAD~1 --name-only 2>/dev/null | grep -q "composer.lock"; then
        log_info "composer.lock o'zgardi - composer install..."
        composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-progress
        log_success "Composer install muvaffaqiyatli"
    else
        log_info "composer.lock o'zgarmadi - skip"
    fi

    # 6. MIGRATIONS (faqat kerak bo'lsa)
    if git diff HEAD~1 --name-only 2>/dev/null | grep -q "database/migrations"; then
        log_info "Yangi migration bor - ishga tushirish..."
        php artisan migrate --force
        log_success "Migrations muvaffaqiyatli"
    else
        log_info "Yangi migration yo'q - skip"
    fi

    # 7. CACHE QAYTA YARATISH
    log_info "Cache qayta yaratilmoqda..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
    log_success "Cache yangilandi"

    # 8. OWNERSHIP VA PERMISSION FIX (KRITIK!)
    fix_permissions

    # 9. PHP-FPM YAKUNIY RELOAD (yangi cache bilan)
    reload_php_fpm

    # 10. QUEUE RESTART
    php artisan queue:restart 2>/dev/null || true

    # 11. MAINTENANCE MODE OFF
    php artisan up
    log_success "Maintenance mode OFF"

    # 12. HEALTH CHECK
    health_check

    echo ""
    log_info "========================================="
    log_success "  PULL DEPLOY MUVAFFAQIYATLI YAKUNLANDI"
    log_info "========================================="
    echo ""
}

# ===================== FULL DEPLOY =====================
full_deploy() {
    echo ""
    log_info "========================================="
    log_info "  FULL DEPLOY boshlandi"
    log_info "========================================="
    echo ""

    acquire_lock
    check_root_safety
    check_memory
    check_disk

    cd "${APP_DIR}"

    # 1. Maintenance mode
    php artisan down --retry=30 --refresh=5 2>/dev/null || true
    log_success "Maintenance mode ON"

    # 2. Cache tozalash
    clear_caches_before_pull

    # 3. PHP-FPM reload (file descriptorlarni yopish)
    reload_php_fpm

    # 4. Git pull
    log_info "Git pull..."
    git config pull.rebase false 2>/dev/null || true
    if ! git pull origin "$DEPLOY_BRANCH"; then
        log_error "Git pull xatolik!"
        php artisan up 2>/dev/null || true
        exit 1
    fi
    log_success "Git pull muvaffaqiyatli"

    # 5. Composer install (har doim)
    log_info "Composer install..."
    composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-progress
    log_success "Composer muvaffaqiyatli"

    # 6. Migrations (har doim)
    log_info "Migrations..."
    php artisan migrate --force
    log_success "Migrations muvaffaqiyatli"

    # 7. Cache qayta yaratish
    log_info "Cache yaratilmoqda..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
    log_success "Cache yangilandi"

    # 8. Ownership va permission fix
    fix_permissions

    # 9. PHP-FPM reload
    reload_php_fpm

    # 10. Queue restart
    php artisan queue:restart 2>/dev/null || true

    # 11. Maintenance mode off
    php artisan up
    log_success "Maintenance mode OFF"

    # 12. Health check
    health_check

    echo ""
    log_success "FULL DEPLOY MUVAFFAQIYATLI YAKUNLANDI"
    echo ""
}

# ===================== ROLLBACK =====================
rollback() {
    echo ""
    log_info "Rollback boshlandi..."
    echo ""

    acquire_lock
    cd "${APP_DIR}"

    git log --oneline -5
    echo ""
    log_info "Oxirgi 5 ta commit yuqorida"

    php artisan down --retry=30 2>/dev/null || true

    # Cache tozalash
    clear_caches_before_pull
    reload_php_fpm

    git reset --hard HEAD~1

    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

    fix_permissions
    reload_php_fpm
    php artisan queue:restart 2>/dev/null || true

    php artisan up
    health_check

    log_success "Rollback yakunlandi"
}

# ===================== STATUS =====================
status() {
    echo ""
    log_info "=== SERVER STATUS ==="
    echo ""

    # Memory
    free -h
    echo ""

    # Disk
    df -h "${APP_DIR}"
    echo ""

    # PHP-FPM
    if [ -n "$PHP_FPM_SERVICE" ]; then
        log_success "PHP-FPM (${PHP_FPM_SERVICE}): ishlayapti"
    else
        log_error "PHP-FPM: topilmadi yoki ishlamayapti!"
    fi

    # Nginx
    if systemctl is-active --quiet nginx 2>/dev/null; then
        log_success "Nginx: ishlayapti"
    else
        log_error "Nginx: ishlamayapti!"
    fi

    # File descriptor holati
    VIEWS_COUNT=$(find "${APP_DIR}/storage/framework/views" -name "*.php" 2>/dev/null | wc -l)
    log_info "Cached views: ${VIEWS_COUNT} ta fayl"

    # Storage ownership
    STORAGE_OWNER=$(stat -c '%U:%G' "${APP_DIR}/storage" 2>/dev/null || echo "noma'lum")
    log_info "Storage ownership: ${STORAGE_OWNER}"

    # Maintenance mode
    if [ -f "${APP_DIR}/storage/framework/down" ]; then
        log_warning "Maintenance mode: YOQILGAN"
    else
        log_success "Maintenance mode: o'chirilgan"
    fi

    # HTTP
    HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" --max-time 5 "http://localhost" || echo "000")
    if [ "$HTTP_STATUS" = "200" ] || [ "$HTTP_STATUS" = "302" ]; then
        log_success "HTTP: ${HTTP_STATUS}"
    elif [ "$HTTP_STATUS" = "503" ]; then
        log_warning "HTTP: ${HTTP_STATUS} (maintenance mode)"
    else
        log_error "HTTP: ${HTTP_STATUS}"
    fi

    # Lock file
    if [ -f "$LOCK_FILE" ]; then
        log_warning "Deploy lock mavjud (PID: $(cat "$LOCK_FILE" 2>/dev/null))"
    fi

    echo ""
}

# =============================================================================
# MAIN
# =============================================================================

case "${1:-pull}" in
    pull)
        pull_deploy
        ;;
    full)
        full_deploy
        ;;
    rollback)
        rollback
        ;;
    status)
        status
        ;;
    *)
        echo ""
        echo "Usage: $0 {pull|full|rollback|status}"
        echo ""
        echo "  pull     - Git pull + smart cache (DEFAULT, eng xavfsiz)"
        echo "  full     - Composer + migrate + cache (yangi dependency bo'lganda)"
        echo "  rollback - Oxirgi commitga qaytish"
        echo "  status   - Server holatini tekshirish"
        echo ""
        echo "Xavfsiz deploy ketma-ketligi:"
        echo "  1. artisan down   (yangi requestlar to'xtaydi)"
        echo "  2. cache clear    (file descriptor conflict oldini oladi)"
        echo "  3. fpm reload     (ochiq file handlelar yopiladi)"
        echo "  4. git pull       (xavfsiz - hech kim fayllarni ishlatmayapti)"
        echo "  5. chown fix      (www-data ownership qaytariladi)"
        echo "  6. fpm reload     (yangi fayllar yuklanadi)"
        echo "  7. artisan up     (sayt qaytadan ishlaydi)"
        echo ""
        exit 1
        ;;
esac
