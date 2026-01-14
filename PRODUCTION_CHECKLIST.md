# BiznesPilot - Production Deployment Checklist

## Pre-Deployment Checklist

### 1. Environment Configuration
- [ ] Copy `.env.production.example` to `.env.production`
- [ ] Generate new `APP_KEY`: `php artisan key:generate`
- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Set `APP_URL` to production domain (HTTPS)
- [ ] Configure database credentials (non-root user!)
- [ ] Configure Redis connection
- [ ] Configure mail settings (SMTP)
- [ ] Set all API keys (Anthropic, Meta, Google, etc.)

### 2. Security Checklist
- [ ] **CRITICAL**: Rotate all API keys that were exposed in `.env`
- [ ] Verify `.env` is in `.gitignore`
- [ ] Check git history for exposed secrets
- [ ] Enable `SESSION_ENCRYPT=true`
- [ ] Enable `SESSION_SECURE_COOKIE=true`
- [ ] Configure CORS properly if needed
- [ ] Set up SSL/TLS certificate
- [ ] Configure firewall rules

### 3. Database
- [ ] Create production database
- [ ] Create dedicated database user (not root!)
- [ ] Grant minimal required permissions
- [ ] Run migrations: `php artisan migrate --force`
- [ ] Verify all indexes are in place
- [ ] Set up database backups

### 4. Cache & Queue
- [ ] Install and configure Redis
- [ ] Set `CACHE_STORE=redis`
- [ ] Set `QUEUE_CONNECTION=redis`
- [ ] Set `SESSION_DRIVER=redis`
- [ ] Configure Supervisor for queue workers
- [ ] Test queue processing

### 5. Performance Optimization
- [ ] Run `php artisan config:cache`
- [ ] Run `php artisan route:cache`
- [ ] Run `php artisan view:cache`
- [ ] Run `php artisan event:cache`
- [ ] Enable OPcache in PHP
- [ ] Build frontend assets: `npm run build`

### 6. Monitoring & Logging
- [ ] Set `LOG_LEVEL=error` or `warning`
- [ ] Configure log rotation
- [ ] Set up error tracking (Sentry, Bugsnag, etc.)
- [ ] Configure Telegram/Slack alerts for critical errors
- [ ] Test health check endpoints

### 7. Web Server
- [ ] Configure Nginx/Apache
- [ ] Set up SSL certificate (Let's Encrypt)
- [ ] Enable Gzip compression
- [ ] Configure static file caching
- [ ] Set up reverse proxy if needed

### 8. Testing
- [ ] Run test suite: `php artisan test`
- [ ] Test all critical user flows manually
- [ ] Verify health check: `curl https://yourdomain/health/status`
- [ ] Test login/registration
- [ ] Test business creation
- [ ] Test API endpoints

## Deployment Commands

```bash
# 1. Pull latest code
git pull origin main

# 2. Install dependencies
composer install --no-dev --optimize-autoloader
npm ci --production=false

# 3. Build frontend
npm run build

# 4. Run migrations
php artisan migrate --force

# 5. Clear and cache config
php artisan config:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 6. Restart services
sudo systemctl reload php8.2-fpm
php artisan queue:restart

# 7. Verify deployment
curl https://yourdomain/health/status
```

## Rollback Procedure

```bash
# If something goes wrong:
./deploy.sh rollback

# Or manually:
# 1. Point symlink to previous release
# 2. Restart PHP-FPM
# 3. Restart queue workers
```

## Post-Deployment Verification

1. **Health Check**
   ```bash
   curl https://yourdomain/health/status
   # Should return {"status": "healthy", ...}
   ```

2. **Login Test**
   - Try logging in with a test account
   - Verify 2FA works if enabled

3. **Business Operations**
   - Create a test business
   - Add a test lead
   - Check dashboard loads

4. **API Check**
   ```bash
   curl -X POST https://yourdomain/api/v1/auth/login \
     -H "Content-Type: application/json" \
     -d '{"login":"testuser","password":"testpass"}'
   ```

5. **Queue Check**
   - Dispatch a test job
   - Verify it processes

## Monitoring Endpoints

| Endpoint | Purpose |
|----------|---------|
| `/health/ping` | Basic liveness check |
| `/health/status` | Detailed health status |
| `/health/ready` | Kubernetes readiness |
| `/health/live` | Kubernetes liveness |
| `/up` | Laravel built-in health |

## Emergency Contacts

- DevOps Lead: [Contact info]
- Database Admin: [Contact info]
- Backend Lead: [Contact info]

## Important Files

| File | Purpose |
|------|---------|
| `.env.production.example` | Production environment template |
| `deploy.sh` | Deployment script |
| `scripts/server-setup.sh` | Server avtomatik sozlash |
| `scripts/first-deploy.sh` | Birinchi deploy script |
| `.github/workflows/deploy-production.yml` | CI/CD workflow |

## Known Issues & Workarounds

1. **Session issues after deployment**
   - Clear session cache: `php artisan cache:clear`
   - Users may need to re-login

2. **Queue jobs stuck**
   - Restart workers: `php artisan queue:restart`
   - Check Redis connection

3. **Slow queries**
   - Check `storage/logs/slow-queries.log`
   - Add missing indexes

---

*Last updated: January 2026*
