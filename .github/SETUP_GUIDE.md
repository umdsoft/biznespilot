# 🚀 BiznesPilot - CI/CD Setup Guide

## Qadam 1: GitHub Secrets sozlash

GitHub repository → Settings → Secrets and variables → Actions → "New repository secret"

### Qo'shish kerak bo'lgan secretlar:

| Secret nomi | Qiymat | Misol |
|-------------|--------|-------|
| `SSH_PRIVATE_KEY` | Server SSH private key | `-----BEGIN OPENSSH PRIVATE KEY-----...` |
| `SERVER_HOST` | Server IP yoki domain | `185.xxx.xxx.xxx` |
| `SERVER_USER` | SSH username | `deploy` |
| `DEPLOY_PATH` | Loyiha joylashuvi | `/var/www/biznespilot` |
| `HEALTH_URL` | Production URL | `https://biznespilot.uz` |
| `TELEGRAM_BOT_TOKEN` | (optional) Alert uchun | `123456:ABC-xyz...` |
| `TELEGRAM_CHAT_ID` | (optional) Chat ID | `-1001234567890` |

---

## Qadam 2: SSH Key yaratish

### Kompyuteringizda:

```bash
# 1. SSH key yarating
ssh-keygen -t ed25519 -C "github-deploy" -f ~/.ssh/biznespilot_deploy

# 2. Private key ni nusxalang (GitHub Secrets uchun)
cat ~/.ssh/biznespilot_deploy
# Bu PRIVATE KEY ni SSH_PRIVATE_KEY ga qo'ying

# 3. Public key ni serverga qo'shing
cat ~/.ssh/biznespilot_deploy.pub
# Bu PUBLIC KEY ni serverda ~/.ssh/authorized_keys ga qo'ying
```

---

## Qadam 3: Server tayyorlash

### VPS ga SSH qiling va quyidagi scriptni bajaring:

```bash
# Serverga ulaning
ssh root@YOUR_SERVER_IP

# Setup scriptni yuklab oling va bajaring
curl -sSL https://raw.githubusercontent.com/YOUR_REPO/main/scripts/server-setup.sh | bash
```

### Yoki qo'lda bajaring (pastda batafsil)

---

## Qadam 4: Birinchi deploy

```bash
# Lokal kompyuterda:
git add .
git commit -m "Setup CI/CD"
git push origin main

# GitHub Actions avtomatik ishga tushadi!
```

---

## 📊 CI/CD Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                                                                 │
│   git push main                                                 │
│        │                                                        │
│        ▼                                                        │
│   ┌─────────────────────────────────────────────────────────┐  │
│   │              GitHub Actions (CI)                         │  │
│   │                                                          │  │
│   │  1. ✅ Checkout code                                     │  │
│   │  2. ✅ Setup PHP 8.2                                     │  │
│   │  3. ✅ Setup Node.js 20                                  │  │
│   │  4. ✅ Run tests (PHPUnit)                               │  │
│   │  5. ✅ npm ci && npm run build                           │  │
│   │  6. ✅ Upload build artifacts                            │  │
│   └─────────────────────────────────────────────────────────┘  │
│        │                                                        │
│        ▼                                                        │
│   ┌─────────────────────────────────────────────────────────┐  │
│   │              GitHub Actions (CD)                         │  │
│   │                                                          │  │
│   │  7. ✅ SSH to server                                     │  │
│   │  8. ✅ git pull origin main                              │  │
│   │  9. ✅ composer install --no-dev                         │  │
│   │ 10. ✅ php artisan migrate --force                       │  │
│   │ 11. ✅ rsync public/build/                               │  │
│   │ 12. ✅ php artisan config:cache                          │  │
│   │ 13. ✅ php artisan queue:restart                         │  │
│   │ 14. ✅ Health check                                      │  │
│   │ 15. ✅ Telegram notification                             │  │
│   └─────────────────────────────────────────────────────────┘  │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## ❓ Muammolar va Yechimlari

### SSH ulanish xatosi
```
Permission denied (publickey)
```
**Yechim:** Public key serverda `~/.ssh/authorized_keys` ga qo'shilganini tekshiring

### Build xatosi
```
npm ERR! code ENOENT
```
**Yechim:** `package-lock.json` git da borligini tekshiring

### Health check failed
```
Health check failed! Status: 000
```
**Yechim:** Nginx va PHP-FPM ishlayotganini tekshiring:
```bash
sudo systemctl status nginx
sudo systemctl status php8.2-fpm
```

---

## 📞 Yordam

Muammo bo'lsa:
1. GitHub Actions logs ni tekshiring
2. Server logs: `tail -f /var/www/biznespilot/storage/logs/laravel.log`
3. Nginx logs: `tail -f /var/log/nginx/error.log`
