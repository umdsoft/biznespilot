#!/bin/bash

# =============================================================================
# BIZNESPILOT - PRODUCTION DEPLOYMENT SCRIPT (MEMORY-SAFE)
# =============================================================================
# Usage: ./deploy.sh [option]
# Options:
#   pull    - Git pull + cache (eng tez, eng xavfsiz) [DEFAULT]
#   full    - Full deployment (composer + migrate + cache)
#   rollback - Rollback to previous version
# =============================================================================

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Configuration
APP_DIR="/var/www/biznespilot"
MIN_FREE_MB=200

# Functions
log_info()    { echo -e "${BLUE}[INFO]${NC} $1"; }
log_success() { echo -e "${GREEN}[OK]${NC} $1"; }
log_warning() { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_error()   { echo -e "${RED}[ERROR]${NC} $1"; }

# PATH xavfsizligi — OOM dan keyin PATH buzilishini oldini oladi
export PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin

# ===================== MEMORY TEKSHIRISH =====================
check_memory() {
    AVAIL_MB=$(free -m | awk 'NR==2{print $7}')
    SWAP_MB=$(free -m | awk 'NR==3{print $2}')
    TOTAL_MB=$(free -m | awk 'NR==2{print $2}')

    log_info "RAM: ${TOTAL_MB}MB | Mavjud: ${AVAIL_MB}MB | Swap: ${SWAP_MB}MB"

    if [ "$AVAIL_MB" -lt "$MIN_FREE_MB" ]; then
        log_error "Yetarli RAM yo'q! Mavjud: ${AVAIL_MB}MB, Kerak: ${MIN_FREE_MB}MB"
        log_info "Keraksiz jarayonlarni tozalash..."

        # Queue worker larni to'xtatish (RAM bo'shatish)
        cd "${APP_DIR}"
        php artisan queue:restart 2>/dev/null || true
        sleep 3

        # Qayta tekshirish
        AVAIL_MB=$(free -m | awk 'NR==2{print $7}')
        if [ "$AVAIL_MB" -lt "$MIN_FREE_MB" ]; then
            log_error "RAM hali ham yetarli emas: ${AVAIL_MB}MB. Deploy to'xtatildi."
            exit 1
        fi
    fi

    # Swap tekshirish va yaratish
    if [ "$SWAP_MB" -lt 1024 ]; then
        log_warning "Swap ${SWAP_MB}MB — kam! 2GB swap yaratish tavsiya etiladi."
        log_info "Swap yaratish: sudo fallocate -l 2G /swapfile && sudo chmod 600 /swapfile && sudo mkswap /swapfile && sudo swapon /swapfile"
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

# ===================== PULL DEPLOY (ASOSIY) =====================
# Localda npm run build qilib, git push qilgandan keyin ishlatiladi
# Serverda npm/build ISHLAMAYDI — faqat git pull + cache
pull_deploy() {
    log_info "========================================="
    log_info "  PULL DEPLOY boshlandi"
    log_info "========================================="

    check_memory
    check_disk

    cd "${APP_DIR}"

    # Maintenance mode
    php artisan down --retry=30 --refresh=5 2>/dev/null || true
    log_info "Maintenance mode yoqildi"

    # View cache tozalash — PHP-FPM bilan file descriptor conflict oldini olish
    php artisan view:clear 2>/dev/null || true
    log_info "View cache tozalandi"

    # Git pull
    log_info "Git pull..."
    git config pull.rebase false 2>/dev/null || true
    git pull origin main || {
        log_error "Git pull xatolik! Conflict bo'lishi mumkin."
        log_info "Tuzatish: git reset --hard origin/main"
        php artisan up 2>/dev/null || true
        exit 1
    }
    log_success "Git pull muvaffaqiyatli"

    # Composer install (faqat yangi dependency bo'lsa)
    if git diff HEAD~1 --name-only | grep -q "composer.lock"; then
        log_info "composer.lock o'zgardi — composer install..."
        composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-progress
        log_success "Composer install muvaffaqiyatli"
    else
        log_info "composer.lock o'zgarmadi — skip"
    fi

    # Migrations (faqat yangi migration bo'lsa)
    if git diff HEAD~1 --name-only | grep -q "database/migrations"; then
        log_info "Yangi migration bor — ishga tushirish..."
        php artisan migrate --force
        log_success "Migrations muvaffaqiyatli"
    else
        log_info "Yangi migration yo'q — skip"
    fi

    # Cache
    log_info "Cache yangilash..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
    log_success "Cache yangilandi"

    # Servicesni reload
    if systemctl is-active --quiet php8.2-fpm 2>/dev/null; then
        sudo systemctl reload php8.2-fpm
        log_success "PHP-FPM reloaded"
    elif systemctl is-active --quiet php8.3-fpm 2>/dev/null; then
        sudo systemctl reload php8.3-fpm
        log_success "PHP-FPM reloaded"
    fi

    php artisan queue:restart 2>/dev/null || true

    # Maintenance mode off
    php artisan up
    log_info "Maintenance mode o'chirildi"

    # Health check
    sleep 5
    HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" "http://localhost" || echo "000")
    if [ "$HTTP_STATUS" = "200" ] || [ "$HTTP_STATUS" = "302" ]; then
        log_success "Health check o'tdi (HTTP ${HTTP_STATUS})"
    else
        log_warning "Health check: HTTP ${HTTP_STATUS} — tekshiring!"
    fi

    log_info "========================================="
    log_success "  DEPLOY YAKUNLANDI"
    log_info "========================================="
}

# ===================== FULL DEPLOY =====================
full_deploy() {
    log_info "========================================="
    log_info "  FULL DEPLOY boshlandi"
    log_info "========================================="

    check_memory
    check_disk

    cd "${APP_DIR}"

    php artisan down --retry=30 --refresh=5 2>/dev/null || true

    # Git pull
    git config pull.rebase false 2>/dev/null || true
    git pull origin main || {
        log_error "Git pull xatolik!"
        php artisan up 2>/dev/null || true
        exit 1
    }

    # Composer
    log_info "Composer install..."
    composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-progress
    log_success "Composer muvaffaqiyatli"

    # Migrations
    log_info "Migrations..."
    php artisan migrate --force
    log_success "Migrations muvaffaqiyatli"

    # Cache
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
    log_success "Cache yangilandi"

    # Permissions
    chmod -R 775 storage bootstrap/cache
    log_success "Permissions o'rnatildi"

    # Restart
    if systemctl is-active --quiet php8.2-fpm 2>/dev/null; then
        sudo systemctl reload php8.2-fpm
    elif systemctl is-active --quiet php8.3-fpm 2>/dev/null; then
        sudo systemctl reload php8.3-fpm
    fi
    php artisan queue:restart 2>/dev/null || true

    php artisan up

    sleep 5
    HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" "http://localhost" || echo "000")
    log_info "Health check: HTTP ${HTTP_STATUS}"

    log_success "FULL DEPLOY YAKUNLANDI"
}

# ===================== ROLLBACK =====================
rollback() {
    log_info "Rollback boshlandi..."

    cd "${APP_DIR}"

    # Oxirgi commitga qaytish
    git log --oneline -5
    log_info "Oxirgi 5 ta commit yuqorida"

    php artisan down --retry=30 2>/dev/null || true

    git reset --hard HEAD~1
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

    if systemctl is-active --quiet php8.2-fpm 2>/dev/null; then
        sudo systemctl reload php8.2-fpm
    elif systemctl is-active --quiet php8.3-fpm 2>/dev/null; then
        sudo systemctl reload php8.3-fpm
    fi
    php artisan queue:restart 2>/dev/null || true

    php artisan up
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
    if systemctl is-active --quiet php8.2-fpm 2>/dev/null; then
        log_success "PHP-FPM: ishlayapti"
    else
        log_error "PHP-FPM: ishlamayapti!"
    fi

    # Nginx
    if systemctl is-active --quiet nginx 2>/dev/null; then
        log_success "Nginx: ishlayapti"
    else
        log_error "Nginx: ishlamayapti!"
    fi

    # Queue
    cd "${APP_DIR}"
    PENDING=$(php artisan queue:monitor 2>/dev/null | head -5 || echo "N/A")
    log_info "Queue: ${PENDING}"

    # HTTP
    HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" "http://localhost" || echo "000")
    if [ "$HTTP_STATUS" = "200" ] || [ "$HTTP_STATUS" = "302" ]; then
        log_success "HTTP: ${HTTP_STATUS}"
    else
        log_error "HTTP: ${HTTP_STATUS}"
    fi

    echo ""
}

# =============================================================================
# Main
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
        echo "  status   - Server holatini ko'rish"
        echo ""
        exit 1
        ;;
esac
