# 🚀 Panduan Deploy PadelBook ke VPS (Ubuntu 22.04 LTS)

## Prasyarat
- VPS Ubuntu 22.04 (minimal 1GB RAM, 20GB Storage)
- Domain yang sudah diarahkan ke IP VPS
- Akses SSH sebagai root atau user sudo

---

## TAHAP 1 — Persiapan Server

```bash
# Update sistem
sudo apt update && sudo apt upgrade -y

# Install dependencies
sudo apt install -y git curl unzip software-properties-common

# Install PHP 8.2 + ekstensi yang dibutuhkan
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.2 php8.2-fpm php8.2-cli php8.2-common \
    php8.2-mysql php8.2-sqlite3 php8.2-gd php8.2-curl \
    php8.2-mbstring php8.2-xml php8.2-zip php8.2-bcmath \
    php8.2-tokenizer php8.2-intl

# Install Nginx
sudo apt install -y nginx

# Install MySQL (pilih ini ATAU SQLite di bawah)
sudo apt install -y mysql-server
sudo mysql_secure_installation

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer
```

---

## TAHAP 2 — Upload Kode ke VPS

### Opsi A: Via Git (Direkomendasikan)
```bash
# Di laptop Anda, push ke GitHub dulu:
# git add . && git commit -m "deploy" && git push

# Di VPS:
cd /var/www
sudo git clone https://github.com/USERNAME/REPO_NAME.git padelbook
sudo chown -R www-data:www-data /var/www/padelbook
cd /var/www/padelbook
```

### Opsi B: Via SCP (Upload langsung dari laptop Windows)
```powershell
# Jalankan di PowerShell laptop Anda:
scp -r "c:\Semester 4\Rekayasa Perangkat Lunak\TUGAS AKHIR\*" root@IP_VPS:/var/www/padelbook/
```

---

## TAHAP 3 — Setup Project Laravel

```bash
cd /var/www/padelbook

# Install PHP dependencies (tanpa dev dependencies)
composer install --no-dev --optimize-autoloader

# Copy & konfigurasi .env
cp .env.example .env
nano .env   # Edit isi .env (lihat bagian konfigurasi di bawah)

# Generate APP_KEY
php artisan key:generate

# Set permission folder
sudo chown -R www-data:www-data /var/www/padelbook
sudo chmod -R 755 /var/www/padelbook
sudo chmod -R 775 /var/www/padelbook/storage
sudo chmod -R 775 /var/www/padelbook/bootstrap/cache

# Buat symlink storage
php artisan storage:link
```

---

## TAHAP 4 — Konfigurasi .env untuk Production

Edit file `/var/www/padelbook/.env` dan isi dengan nilai berikut:

```env
APP_NAME=PadelBook
APP_ENV=production
APP_KEY=                        # Sudah diisi oleh php artisan key:generate
APP_DEBUG=false
APP_URL=https://domain-anda.com # Ganti dengan domain Anda

LOG_CHANNEL=stack
LOG_LEVEL=error

# === DATABASE ===
# Pilihan 1: MySQL (lebih stabil untuk production)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=padelbook
DB_USERNAME=padelbook_user
DB_PASSWORD=PASSWORD_KUAT_ANDA

# Pilihan 2: SQLite (simpel, tanpa konfigurasi tambahan)
# DB_CONNECTION=sqlite

# === MIDTRANS (Production) ===
MIDTRANS_SERVER_KEY=Mid-server-XXXXXXXX  # Ganti ke key PRODUCTION
MIDTRANS_CLIENT_KEY=Mid-client-XXXXXXXX  # Ganti ke key PRODUCTION
MIDTRANS_IS_PRODUCTION=true              # WAJIB true saat production

# === GOOGLE OAUTH ===
GOOGLE_CLIENT_ID=XXXX.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-XXXX
GOOGLE_REDIRECT_URI=https://domain-anda.com/auth/google/callback

# === EMAIL ===
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=padelbook11@gmail.com
MAIL_PASSWORD=npzusezhnpwbkrqp
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=padelbook11@gmail.com
MAIL_FROM_NAME="PadelBook"

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
```

---

## TAHAP 5 — Setup Database MySQL

```bash
# Masuk ke MySQL
sudo mysql

# Buat database dan user
CREATE DATABASE padelbook CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'padelbook_user'@'localhost' IDENTIFIED BY 'PASSWORD_KUAT_ANDA';
GRANT ALL PRIVILEGES ON padelbook.* TO 'padelbook_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Jalankan migration
cd /var/www/padelbook
php artisan migrate --force

# (Opsional) Jalankan seeder jika ada
# php artisan db:seed --force
```

### Jika Pakai SQLite (alternatif):
```bash
# Buat file database SQLite
touch /var/www/padelbook/database/database.sqlite
sudo chown www-data:www-data /var/www/padelbook/database/database.sqlite

# Jalankan migration
php artisan migrate --force
```

---

## TAHAP 6 — Konfigurasi Nginx

```bash
# Buat konfigurasi Nginx untuk PadelBook
sudo nano /etc/nginx/sites-available/padelbook
```

Isi dengan konfigurasi berikut:

```nginx
server {
    listen 80;
    server_name domain-anda.com www.domain-anda.com;
    root /var/www/padelbook/public;

    index index.php index.html;

    # Keamanan: sembunyikan info server
    server_tokens off;

    # Kompresi Gzip
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Cache aset statis
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    client_max_body_size 10M;
}
```

```bash
# Aktifkan site dan test konfigurasi
sudo ln -s /etc/nginx/sites-available/padelbook /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

---

## TAHAP 7 — SSL/HTTPS dengan Let's Encrypt (Gratis)

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Dapatkan sertifikat SSL otomatis
sudo certbot --nginx -d domain-anda.com -d www.domain-anda.com

# Certbot otomatis update konfigurasi Nginx untuk HTTPS
# Cek auto-renewal
sudo certbot renew --dry-run
```

---

## TAHAP 8 — Optimasi Production Laravel

```bash
cd /var/www/padelbook

# Cache config, route, dan view
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

---

## TAHAP 9 — Setup Cron Job (Scheduler Laravel)

```bash
# Buka crontab untuk user www-data
sudo crontab -u www-data -e

# Tambahkan baris berikut:
* * * * * cd /var/www/padelbook && php artisan schedule:run >> /dev/null 2>&1
```

Scheduler ini menjalankan:
- **Setiap jam**: Auto-cancel booking pending > 2 jam + auto-complete booking selesai
- **Setiap pukul 08:00**: Kirim email reminder booking H-1

---

## TAHAP 10 — Update Konfigurasi Google OAuth

Di [Google Cloud Console](https://console.cloud.google.com):
1. Pilih project PadelBook Anda
2. Masuk ke **APIs & Services → Credentials**
3. Edit OAuth 2.0 Client ID Anda
4. Di **Authorized redirect URIs**, tambahkan:
   `https://domain-anda.com/auth/google/callback`
5. Klik Save

---

## TAHAP 11 — Update Webhook Midtrans Production

Di [Dashboard Midtrans Production](https://dashboard.midtrans.com):
1. Masuk ke **Settings → Configuration**
2. **Payment Notification URL**: `https://domain-anda.com/booking/midtrans-notification`
3. **Finish Redirect URL**: `https://domain-anda.com/dashboard/bookings`
4. **Error Redirect URL**: `https://domain-anda.com/dashboard/bookings`
5. Klik Save

> ⚠️ **PENTING**: Ganti `MIDTRANS_IS_PRODUCTION=true` dan gunakan **Server Key + Client Key PRODUCTION** (bukan Sandbox) dari dashboard Midtrans Production.

---

## TAHAP 12 — Verifikasi Final

```bash
# Cek semua service berjalan
sudo systemctl status nginx
sudo systemctl status php8.2-fpm
sudo systemctl status mysql    # jika pakai MySQL

# Cek log Laravel
tail -f /var/www/padelbook/storage/logs/laravel.log

# Test scheduler
php artisan schedule:run
```

---

## 🔧 Perintah Berguna Setelah Deploy

```bash
# Saat ada update kode dari Git:
cd /var/www/padelbook
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize

# Restart PHP jika ada masalah:
sudo systemctl restart php8.2-fpm

# Lihat log error:
tail -100 /var/www/padelbook/storage/logs/laravel.log
```

---

## 📋 Checklist Sebelum Go Live

- [ ] `APP_DEBUG=false` di .env
- [ ] `APP_ENV=production` di .env  
- [ ] `MIDTRANS_IS_PRODUCTION=true` di .env
- [ ] SSL/HTTPS aktif (sertifikat Let's Encrypt)
- [ ] Google OAuth redirect URI sudah diupdate ke domain production
- [ ] Midtrans webhook URL sudah diupdate ke domain production
- [ ] Cron job sudah aktif (`crontab -l` untuk verifikasi)
- [ ] `php artisan optimize` sudah dijalankan
- [ ] Storage permission sudah benar (`755` / `775`)
