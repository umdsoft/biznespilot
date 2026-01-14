#!/bin/bash

# =============================================================================
# BIZNESPILOT - FIRST DEPLOY SCRIPT
# =============================================================================
# Server sozlangandan keyin birinchi deploy uchun
# Ishlatish: bash first-deploy.sh
# =============================================================================

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log_info() { echo -e "${BLUE}[INFO]${NC} $1"; }
log_success() { echo -e "${GREEN}[SUCCESS]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }

DEPLOY_PATH="/var/www/biznespilot"
GITHUB_REPO="https://github.com/YOUR_USERNAME/biznespilot.git"  # O'zgartiring!

# =============================================================================
# 1. CLONE REPOSITORY
# =============================================================================
log_info "Repository klonlash..."
cd /var/www
if [ -d "$DEPLOY_PATH/.git" ]; then
    log_info "Repository allaqachon mavjud, yangilanmoqda..."
    cd $DEPLOY_PATH
    git fetch origin main
    git reset --hard origin/main
else
    rm -rf $DEPLOY_PATH
    git clone $GITHUB_REPO $DEPLOY_PATH
    cd $DEPLOY_PATH
fi

# =============================================================================
# 2. SET PERMISSIONS
# =============================================================================
log_info "Ruxsatlarni sozlash..."
chown -R deploy:www-data $DEPLOY_PATH
chmod -R 775 $DEPLOY_PATH/storage $DEPLOY_PATH/bootstrap/cache

# =============================================================================
# 3. INSTALL COMPOSER DEPENDENCIES
# =============================================================================
log_info "Composer dependencies o'rnatish..."
cd $DEPLOY_PATH
sudo -u deploy composer install --no-dev --optimize-autoloader --no-interaction

# =============================================================================
# 4. CREATE .ENV FILE
# =============================================================================
if [ ! -f "$DEPLOY_PATH/.env" ]; then
    log_info ".env faylini yaratish..."
    cp $DEPLOY_PATH/.env.production.example $DEPLOY_PATH/.env

    # Generate app key
    sudo -u deploy php artisan key:generate

    log_error ".env faylini tahrirlang: nano $DEPLOY_PATH/.env"
    log_error "Database credentials va boshqa sozlamalarni qo'shing!"
    exit 1
fi

# =============================================================================
# 5. BUILD FRONTEND (birinchi marta serverda)
# =============================================================================
log_info "Frontend build qilish..."
cd $DEPLOY_PATH
npm ci
npm run build
rm -rf node_modules  # Tozalash

# =============================================================================
# 6. RUN MIGRATIONS
# =============================================================================
log_info "Database migratsiyalari..."
sudo -u deploy php artisan migrate --force

# =============================================================================
# 7. CACHE CONFIGURATION
# =============================================================================
log_info "Konfiguratsiyani keshlash..."
sudo -u deploy php artisan config:cache
sudo -u deploy php artisan route:cache
sudo -u deploy php artisan view:cache
sudo -u deploy php artisan event:cache

# =============================================================================
# 8. CREATE STORAGE LINK
# =============================================================================
log_info "Storage link yaratish..."
sudo -u deploy php artisan storage:link

# =============================================================================
# 9. RESTART SERVICES
# =============================================================================
log_info "Servislarni qayta ishga tushirish..."
systemctl restart php8.2-fpm
systemctl restart nginx
supervisorctl restart all

# =============================================================================
# 10. HEALTH CHECK
# =============================================================================
log_info "Health check..."
sleep 3
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/health/ping || echo "000")

if [ "$HTTP_STATUS" = "200" ]; then
    log_success "Health check passed!"
else
    log_error "Health check failed! Status: $HTTP_STATUS"
    log_error "Loglarni tekshiring: tail -f $DEPLOY_PATH/storage/logs/laravel.log"
fi

# =============================================================================
# DONE
# =============================================================================
echo ""
log_success "=========================================="
log_success "BIRINCHI DEPLOY YAKUNLANDI!"
log_success "=========================================="
echo ""
echo "Sayt manzili: http://$(curl -s ifconfig.me)"
echo ""
echo "Keyingi qadamlar:"
echo "1. Domain DNS ni serverga yo'naltiring"
echo "2. SSL o'rnating: certbot --nginx -d yourdomain.uz"
echo "3. GitHub push = Avtomatik deploy!"
echo ""
