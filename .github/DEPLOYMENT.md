# BiznesPilot - CI/CD Deployment Guide

## Overview

Bu loyiha GitHub Actions orqali avtomatik CI/CD pipeline ishlatadi.

### Workflows

| Workflow | Trigger | Maqsad |
|----------|---------|--------|
| `ci.yml` | Push to any branch, PRs | Test, Lint, Build verification |
| `deploy-staging.yml` | Push to `develop` | Staging serverga deploy |
| `deploy-production.yml` | Push to `main` | Production serverga deploy |
| `docker-build.yml` | Release tags (`v*`) | Docker image build & push |

---

## Required GitHub Secrets

GitHub repository settings > Secrets and variables > Actions da quyidagi secretlarni qo'shing:

### Production Deployment

| Secret | Tavsif | Misol |
|--------|--------|-------|
| `SSH_PRIVATE_KEY` | Server SSH private key | `-----BEGIN OPENSSH PRIVATE KEY-----...` |
| `SERVER_HOST` | Production server IP/domain | `192.168.1.100` yoki `server.example.com` |
| `SERVER_USER` | SSH user | `deploy` |
| `DEPLOY_PATH` | Server dagi loyiha path | `/var/www/biznespilot` |
| `HEALTH_URL` | Production URL | `https://biznespilot.uz` |

### Staging Deployment

| Secret | Tavsif |
|--------|--------|
| `STAGING_SSH_KEY` | Staging server SSH key |
| `STAGING_HOST` | Staging server IP |
| `STAGING_USER` | Staging SSH user |
| `STAGING_PATH` | Staging deploy path |

### Notifications (Optional)

| Secret | Tavsif |
|--------|--------|
| `TELEGRAM_BOT_TOKEN` | Telegram bot token for notifications |
| `TELEGRAM_CHAT_ID` | Telegram chat/group ID |

---

## Server Preparation

### 1. SSH Key Setup

```bash
# Local kompyuterda SSH key yarating
ssh-keygen -t ed25519 -C "github-actions-deploy"

# Public key ni serverga qo'shing
cat ~/.ssh/id_ed25519.pub | ssh user@server "cat >> ~/.ssh/authorized_keys"

# Private key ni GitHub Secrets ga qo'shing
cat ~/.ssh/id_ed25519
```

### 2. Server Requirements

```bash
# PHP 8.2+
sudo apt install php8.2-fpm php8.2-cli php8.2-mysql php8.2-redis php8.2-mbstring php8.2-xml php8.2-curl php8.2-zip php8.2-gd

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Node.js (optional, if building on server)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Redis
sudo apt install redis-server

# Nginx
sudo apt install nginx

# Git
sudo apt install git
```

### 3. Directory Setup

```bash
# Create deploy directory
sudo mkdir -p /var/www/biznespilot
sudo chown deploy:www-data /var/www/biznespilot

# Clone repository
cd /var/www/biznespilot
git clone https://github.com/your-org/biznespilot.git .

# Setup shared files
cp .env.production.example .env
php artisan key:generate

# Set permissions
sudo chown -R deploy:www-data .
sudo chmod -R 775 storage bootstrap/cache
```

### 4. Nginx Configuration

```nginx
# /etc/nginx/sites-available/biznespilot
server {
    listen 80;
    server_name biznespilot.uz www.biznespilot.uz;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name biznespilot.uz www.biznespilot.uz;

    root /var/www/biznespilot/public;
    index index.php;

    ssl_certificate /etc/letsencrypt/live/biznespilot.uz/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/biznespilot.uz/privkey.pem;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 5. Supervisor for Queue Workers

```ini
# /etc/supervisor/conf.d/biznespilot-worker.conf
[program:biznespilot-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/biznespilot/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=deploy
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/biznespilot/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start biznespilot-worker:*
```

---

## Deployment Flow

### Automatic (Push to main)

```
1. Developer pushes to main branch
2. GitHub Actions triggers deploy-production.yml
3. Tests run (can be skipped for emergency)
4. Assets are built
5. SSH connection to server
6. Git pull, composer install, migrations
7. Cache clear and rebuild
8. Queue workers restart
9. Health check verification
10. Telegram notification sent
```

### Manual Deployment

```bash
# From GitHub Actions UI:
# 1. Go to Actions tab
# 2. Select "CD - Deploy Production"
# 3. Click "Run workflow"
# 4. Optionally check "Skip tests" for emergency

# Or via command line:
gh workflow run deploy-production.yml
```

### Docker Deployment

```bash
# Create a new release tag
git tag -a v1.2.3 -m "Release v1.2.3"
git push origin v1.2.3

# This triggers docker-build.yml which:
# 1. Builds Docker image
# 2. Pushes to GitHub Container Registry
# 3. Deploys to production via docker-compose
```

---

## Rollback

### Automatic (via GitHub Actions)

If deployment fails, the workflow attempts automatic rollback.

### Manual Rollback

```bash
# SSH to server
ssh deploy@server

# Go to project directory
cd /var/www/biznespilot

# Rollback to previous commit
git reset --hard HEAD~1

# Reinstall dependencies
composer install --no-dev --optimize-autoloader

# Run migrations (if needed)
php artisan migrate --force

# Clear and rebuild cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart queue workers
php artisan queue:restart
```

### Docker Rollback

```bash
# SSH to server
ssh deploy@server
cd /var/www/biznespilot

# Pull previous image tag
docker pull ghcr.io/your-org/biznespilot:v1.2.2

# Update docker-compose.yml with previous tag
# Then restart
docker-compose -f docker-compose.prod.yml up -d
```

---

## Monitoring

### Health Check Endpoints

```bash
# Basic ping
curl https://biznespilot.uz/health/ping

# Detailed status
curl https://biznespilot.uz/health/status

# Kubernetes readiness
curl https://biznespilot.uz/health/ready

# Kubernetes liveness
curl https://biznespilot.uz/health/live
```

### Logs

```bash
# Application logs
tail -f /var/www/biznespilot/storage/logs/laravel.log

# Queue worker logs
tail -f /var/www/biznespilot/storage/logs/worker.log

# Nginx logs
tail -f /var/log/nginx/access.log
tail -f /var/log/nginx/error.log
```

---

## Troubleshooting

### Deployment Failed

1. Check GitHub Actions logs
2. SSH to server and check manually:
   ```bash
   cd /var/www/biznespilot
   php artisan config:clear
   composer install
   php artisan migrate --force
   ```

### Queue Not Processing

```bash
# Check supervisor status
sudo supervisorctl status

# Restart workers
sudo supervisorctl restart biznespilot-worker:*

# Or via artisan
php artisan queue:restart
```

### Permission Issues

```bash
sudo chown -R deploy:www-data /var/www/biznespilot
sudo chmod -R 775 storage bootstrap/cache
```

### Cache Issues

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Environment Variables

Production serverda `.env` faylda quyidagilar bo'lishi SHART:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://biznespilot.uz

# Strong database password
DB_PASSWORD=<strong-password>

# Redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

# Session security
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
```

---

## Security Notes

1. **SSH Keys**: Har bir environment uchun alohida key ishlating
2. **Secrets**: Hech qachon secretlarni kodga yozmang
3. **Permissions**: Deploy user faqat kerakli permissionlarga ega bo'lsin
4. **Firewall**: SSH portini faqat GitHub Actions IP laridan oching (optional)
5. **Audit**: Deploymentlarni muntazam tekshiring

---

*Muallif: BiznesPilot Team*
*Oxirgi yangilanish: Yanvar 2026*
