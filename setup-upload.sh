#!/usr/bin/env bash
# ==============================================================================
# Script Otomatis: Konfigurasi Upload & Permission Liga Dam Batu
# ==============================================================================

set -e

# Pastikan script dijalankan sebagai root (sudo)
if [ "$EUID" -ne 0 ]; then
    echo "❌ Error: Script ini harus dijalankan dengan hak akses root / sudo."
    echo "👉 Gunakan: sudo bash $0"
    exit 1
fi

PROJECT_DIR="/var/www/poetracoding.net/dambatu"
PHP_FPM_INI="/etc/php/8.2/fpm/php.ini"
PHP_CLI_INI="/etc/php/8.2/cli/php.ini"
NGINX_CONF_D="/etc/nginx/conf.d/upload_limits.conf"
NGINX_SITE="/etc/nginx/sites-available/dambatu-8080"

echo "=========================================================="
echo "🚀 Memulai Konfigurasi Server & Upload Permission..."
echo "=========================================================="

# 1. Konfigurasi PHP (FPM & CLI)
echo "⚙️  [1/5] Mengatur limit upload di PHP 8.2..."
if [ -f "$PHP_FPM_INI" ]; then
    sed -i 's/^upload_max_filesize = .*/upload_max_filesize = 20M/' "$PHP_FPM_INI"
    sed -i 's/^post_max_size = .*/post_max_size = 25M/' "$PHP_FPM_INI"
    sed -i 's/^memory_limit = .*/memory_limit = 256M/' "$PHP_FPM_INI"
    echo "   ✅ php.ini (FPM) berhasil diperbarui (upload: 20M, post: 25M)."
fi

if [ -f "$PHP_CLI_INI" ]; then
    sed -i 's/^upload_max_filesize = .*/upload_max_filesize = 20M/' "$PHP_CLI_INI"
    sed -i 's/^post_max_size = .*/post_max_size = 25M/' "$PHP_CLI_INI"
    echo "   ✅ php.ini (CLI) berhasil diperbarui."
fi

# 2. Konfigurasi Nginx (client_max_body_size)
echo "⚙️  [2/5] Mengatur client_max_body_size di Nginx..."
cat << 'EOF' > "$NGINX_CONF_D"
# Konfigurasi batas upload global untuk Nginx
client_max_body_size 25M;
EOF
echo "   ✅ $NGINX_CONF_D dibuat dengan client_max_body_size 25M."

if [ -f "$NGINX_SITE" ]; then
    if ! grep -q "client_max_body_size" "$NGINX_SITE"; then
        sed -i '/server_name/a \    client_max_body_size 25M;' "$NGINX_SITE"
        echo "   ✅ Ditambahkan client_max_body_size ke $NGINX_SITE."
    fi
fi

# 3. Penyiapan Direktori Upload & Storage
echo "📁 [3/5] Membuat direktori upload dan symlink storage..."
mkdir -p "$PROJECT_DIR/public/uploads/berita"
mkdir -p "$PROJECT_DIR/public/uploads/posters"
mkdir -p "$PROJECT_DIR/storage/app/public"
touch "$PROJECT_DIR/public/uploads/berita/.gitkeep"
touch "$PROJECT_DIR/public/uploads/posters/.gitkeep"

# Buat symlink storage jika belum ada
if [ ! -L "$PROJECT_DIR/public/storage" ]; then
    if [ -d "$PROJECT_DIR/public/storage" ]; then
        rm -rf "$PROJECT_DIR/public/storage"
    fi
    sudo -u its php "$PROJECT_DIR/artisan" storage:link 2>/dev/null || ln -s "$PROJECT_DIR/storage/app/public" "$PROJECT_DIR/public/storage"
    echo "   ✅ Symlink public/storage terpasang."
else
    echo "   ℹ️  Symlink public/storage sudah ada."
fi

# 4. Pengaturan Hak Akses (Ownership & Permissions)
echo "🔒 [4/5] Mengatur hak akses kepemilikan folder..."
chown -R its:www-data "$PROJECT_DIR/storage"
chown -R its:www-data "$PROJECT_DIR/bootstrap/cache"
chown -R its:www-data "$PROJECT_DIR/public/uploads"

chmod -R 775 "$PROJECT_DIR/storage"
chmod -R 775 "$PROJECT_DIR/bootstrap/cache"
chmod -R 777 "$PROJECT_DIR/public/uploads"
echo "   ✅ Permission storage (775) dan public/uploads (777) berhasil diatur."

# 5. Restart Layanan (PHP-FPM & Nginx)
echo "🔄 [5/5] Memeriksa konfigurasi Nginx dan me-restart layanan..."
nginx -t
systemctl restart php8.2-fpm
systemctl reload nginx
echo "   ✅ PHP-FPM di-restart dan Nginx di-reload."

echo "=========================================================="
echo "🎉 Selesai! Semua settingan upload berhasil diperbarui."
echo "   - PHP upload limit : 20M"
echo "   - PHP post limit   : 25M"
echo "   - Nginx body size  : 25M"
echo "   - Upload folder    : Writable oleh www-data"
echo "=========================================================="
