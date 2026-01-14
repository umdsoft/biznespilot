#!/bin/bash

# =============================================================================
# BIZNESPILOT - PRODUCTION DEPLOYMENT SCRIPT
# =============================================================================
# Usage: ./deploy.sh [option]
# Options:
#   full    - Full deployment (default)
#   quick   - Quick deployment (skip composer/npm)
#   rollback - Rollback to previous version
# =============================================================================

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
APP_DIR="/var/www/biznespilot"
RELEASES_DIR="${APP_DIR}/releases"
SHARED_DIR="${APP_DIR}/shared"
CURRENT_LINK="${APP_DIR}/current"
KEEP_RELEASES=5
TIMESTAMP=$(date +%Y%m%d%H%M%S)
NEW_RELEASE_DIR="${RELEASES_DIR}/${TIMESTAMP}"

# Functions
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

check_requirements() {
    log_info "Checking requirements..."

    command -v php >/dev/null 2>&1 || { log_error "PHP is required but not installed."; exit 1; }
    command -v composer >/dev/null 2>&1 || { log_error "Composer is required but not installed."; exit 1; }
    command -v npm >/dev/null 2>&1 || { log_error "NPM is required but not installed."; exit 1; }
    command -v redis-cli >/dev/null 2>&1 || log_warning "Redis CLI not found. Redis features may not work."

    PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
    if [[ $(echo "$PHP_VERSION < 8.2" | bc -l) -eq 1 ]]; then
        log_error "PHP 8.2+ is required. Found: $PHP_VERSION"
        exit 1
    fi

    log_success "All requirements met. PHP version: $PHP_VERSION"
}

create_directories() {
    log_info "Creating directory structure..."

    mkdir -p "${RELEASES_DIR}"
    mkdir -p "${SHARED_DIR}/storage/app/public"
    mkdir -p "${SHARED_DIR}/storage/framework/cache"
    mkdir -p "${SHARED_DIR}/storage/framework/sessions"
    mkdir -p "${SHARED_DIR}/storage/framework/views"
    mkdir -p "${SHARED_DIR}/storage/logs"

    log_success "Directories created"
}

clone_repository() {
    log_info "Creating new release: ${TIMESTAMP}..."

    # Copy current code to new release directory
    cp -r . "${NEW_RELEASE_DIR}"

    # Remove unnecessary files
    rm -rf "${NEW_RELEASE_DIR}/.git"
    rm -rf "${NEW_RELEASE_DIR}/node_modules"
    rm -rf "${NEW_RELEASE_DIR}/vendor"
    rm -rf "${NEW_RELEASE_DIR}/tests"
    rm -f "${NEW_RELEASE_DIR}/.env"
    rm -f "${NEW_RELEASE_DIR}/.env.local"

    log_success "New release created"
}

link_shared_resources() {
    log_info "Linking shared resources..."

    # Link storage directory
    rm -rf "${NEW_RELEASE_DIR}/storage"
    ln -sf "${SHARED_DIR}/storage" "${NEW_RELEASE_DIR}/storage"

    # Link .env file
    ln -sf "${SHARED_DIR}/.env" "${NEW_RELEASE_DIR}/.env"

    log_success "Shared resources linked"
}

install_dependencies() {
    log_info "Installing Composer dependencies..."

    cd "${NEW_RELEASE_DIR}"
    composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

    log_info "Installing NPM dependencies..."
    npm ci --production=false

    log_success "Dependencies installed"
}

build_assets() {
    log_info "Building frontend assets..."

    cd "${NEW_RELEASE_DIR}"
    npm run build

    log_success "Assets built"
}

run_migrations() {
    log_info "Running database migrations..."

    cd "${NEW_RELEASE_DIR}"
    php artisan migrate --force

    log_success "Migrations completed"
}

cache_config() {
    log_info "Caching configuration..."

    cd "${NEW_RELEASE_DIR}"

    # Clear old cache
    php artisan cache:clear
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear

    # Generate new cache
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache

    log_success "Configuration cached"
}

set_permissions() {
    log_info "Setting file permissions..."

    cd "${NEW_RELEASE_DIR}"

    # Set ownership (adjust user/group as needed)
    # chown -R www-data:www-data .

    # Set directory permissions
    find . -type d -exec chmod 755 {} \;

    # Set file permissions
    find . -type f -exec chmod 644 {} \;

    # Make artisan executable
    chmod +x artisan

    # Storage and bootstrap/cache need write permissions
    chmod -R 775 "${SHARED_DIR}/storage"
    chmod -R 775 bootstrap/cache

    log_success "Permissions set"
}

activate_release() {
    log_info "Activating new release..."

    # Update symlink atomically
    ln -sfn "${NEW_RELEASE_DIR}" "${CURRENT_LINK}"

    log_success "Release ${TIMESTAMP} activated"
}

restart_services() {
    log_info "Restarting services..."

    # Restart PHP-FPM (adjust service name as needed)
    if systemctl is-active --quiet php8.2-fpm; then
        sudo systemctl reload php8.2-fpm
        log_success "PHP-FPM reloaded"
    fi

    # Restart queue workers
    cd "${CURRENT_LINK}"
    php artisan queue:restart
    log_success "Queue workers restarted"

    # Clear OPcache if available
    if php -m | grep -q OPcache; then
        php artisan opcache:clear 2>/dev/null || true
        log_info "OPcache cleared"
    fi
}

cleanup_old_releases() {
    log_info "Cleaning up old releases..."

    cd "${RELEASES_DIR}"

    # Keep only the last N releases
    ls -1d */ 2>/dev/null | head -n -${KEEP_RELEASES} | xargs -r rm -rf

    log_success "Old releases cleaned up"
}

health_check() {
    log_info "Running health check..."

    sleep 3

    HEALTH_URL="${APP_URL:-http://localhost}/health/ready"

    HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" "${HEALTH_URL}" || echo "000")

    if [ "$HTTP_STATUS" = "200" ]; then
        log_success "Health check passed!"
    else
        log_error "Health check failed! HTTP status: ${HTTP_STATUS}"
        log_warning "Consider rolling back with: ./deploy.sh rollback"
        exit 1
    fi
}

rollback() {
    log_info "Rolling back to previous release..."

    cd "${RELEASES_DIR}"

    # Get previous release
    PREVIOUS=$(ls -1d */ | tail -n 2 | head -n 1 | tr -d '/')

    if [ -z "${PREVIOUS}" ]; then
        log_error "No previous release found!"
        exit 1
    fi

    log_info "Rolling back to: ${PREVIOUS}"

    # Update symlink
    ln -sfn "${RELEASES_DIR}/${PREVIOUS}" "${CURRENT_LINK}"

    # Restart services
    restart_services

    log_success "Rollback completed to release: ${PREVIOUS}"
}

quick_deploy() {
    log_info "Starting quick deployment..."

    cd "${CURRENT_LINK}"

    # Pull latest changes
    git pull origin main

    # Clear and rebuild cache
    cache_config

    # Restart services
    restart_services

    log_success "Quick deployment completed"
}

full_deploy() {
    log_info "Starting full deployment..."

    check_requirements
    create_directories
    clone_repository
    link_shared_resources
    install_dependencies
    build_assets
    run_migrations
    cache_config
    set_permissions
    activate_release
    restart_services
    cleanup_old_releases
    health_check

    log_success "Full deployment completed successfully!"
    log_info "Release: ${TIMESTAMP}"
}

# =============================================================================
# Main
# =============================================================================

case "${1:-full}" in
    full)
        full_deploy
        ;;
    quick)
        quick_deploy
        ;;
    rollback)
        rollback
        ;;
    *)
        echo "Usage: $0 {full|quick|rollback}"
        exit 1
        ;;
esac
