# CD (Continuous Deployment) Sozlash

## GitHub Secrets Qo'shish

GitHub repository → **Settings** → **Secrets and variables** → **Actions** → **New repository secret**

Quyidagi secretlarni qo'shing:

### 1. `SSH_PRIVATE_KEY`
Serverga ulanish uchun SSH private key.

**Qanday olish:**
```bash
# Lokal kompyuterda yangi SSH key yaratish (agar yo'q bo'lsa)
ssh-keygen -t ed25519 -C "github-deploy@biznespilot"

# Private key ni ko'rish (buni GitHub secretga qo'shing)
cat ~/.ssh/id_ed25519

# Public key ni serverga qo'shish
cat ~/.ssh/id_ed25519.pub >> ~/.ssh/authorized_keys
```

> ⚠️ **Muhim:** Butun key ni qo'shing, `-----BEGIN OPENSSH PRIVATE KEY-----` dan `-----END OPENSSH PRIVATE KEY-----` gacha.

### 2. `SERVER_HOST`
Server IP manzili yoki domain.

**Misol:** `123.45.67.89` yoki `biznespilot.uz`

### 3. `SERVER_USER`
SSH foydalanuvchi nomi.

**Misol:** `root` yoki `deploy` yoki `ubuntu`

### 4. `SERVER_PATH`
Loyiha joylashgan papka.

**Qiymat:** `/var/www/biznespilot`

---

## Serverda Tayyorgarlik

### 1. SSH Key qo'shish
```bash
# Serverga kiring
ssh root@your-server-ip

# authorized_keys faylini tekshiring
cat ~/.ssh/authorized_keys

# Agar yo'q bo'lsa, papka va fayl yarating
mkdir -p ~/.ssh
chmod 700 ~/.ssh
touch ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys

# GitHub Actions dan kelgan public key ni qo'shing
echo "ssh-ed25519 AAAA... github-deploy@biznespilot" >> ~/.ssh/authorized_keys
```

### 2. Papka ruxsatlarini tekshirish
```bash
# Papka mavjudligini tekshiring
ls -la /var/www/biznespilot

# Ruxsatlarni sozlang
chown -R www-data:www-data /var/www/biznespilot
chmod -R 755 /var/www/biznespilot
chmod -R 775 /var/www/biznespilot/storage
chmod -R 775 /var/www/biznespilot/bootstrap/cache
```

### 3. .env fayli mavjudligini tekshirish
```bash
# .env fayli serverda qoladi (rsync exclude qilingan)
ls -la /var/www/biznespilot/.env
```

---

## Deploy Jarayoni

Har safar `main` branchga push qilinganda:

1. ✅ CI - Test & Build ishga tushadi
2. ✅ Barcha testlar o'tadi
3. ✅ CD - Deploy ishga tushadi
4. ✅ Fayllar serverga yuboriladi
5. ✅ Migrationlar ishga tushadi
6. ✅ Cachelar tozalanadi
7. ✅ Queue restart bo'ladi

---

## Manual Deploy

Agar qo'lda deploy qilmoqchi bo'lsangiz:

1. GitHub → **Actions** → **CD - Deploy to Production**
2. **Run workflow** tugmasini bosing
3. Branch tanlang: `main`
4. **Run workflow** ni bosing

---

## Troubleshooting

### SSH ulanish xatosi
```
Permission denied (publickey)
```
**Yechim:** SSH key to'g'ri qo'shilganini tekshiring.

### Rsync xatosi
```
rsync: connection unexpectedly closed
```
**Yechim:** Server host va user to'g'ri ekanini tekshiring.

### Migration xatosi
```
Migration failed
```
**Yechim:** Database ulanish `.env` da to'g'ri sozlanganini tekshiring.
