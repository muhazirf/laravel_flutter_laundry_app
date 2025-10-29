#!/bin/bash
set -e

# Cek apakah sudah ada Laravel di /var/www/html
if [ ! -f /var/www/html/composer.json ]; then
  echo "Laravel belum ada di /var/www/html, memeriksa direktori src..."
  
  # Cek apakah ada Laravel di direktori src
  if [ -f /src/composer.json ]; then
    echo "Menemukan Laravel di direktori src, menyalin ke /var/www/html..."
    cp -r /src/. /var/www/html/
    echo "Laravel berhasil disalin dari src ke /var/www/html"
  else
    echo "Tidak menemukan Laravel di src, melakukan instalasi baru..."
    cd /var/www/html
    composer create-project laravel/laravel="12.*" .
  fi
else
  echo "Laravel sudah terpasang di /var/www/html, skip instalasi."
fi

# Ganti nilai DB_HOST, DB_DATABASE, DLL dari ENV Docker
echo "Menyesuaikan .env dengan variabel environment. Dengan koneksi ${DB_CONNECTION}"
sed -i "s/^DB_CONNECTION=.*/DB_CONNECTION=${DB_CONNECTION}/" .env
sed -i "s/^#* *DB_HOST=.*/DB_HOST=${DB_HOST}/" .env
sed -i 's/^#* *DB_PORT=3306*/DB_PORT=3306/' .env
sed -i "s/^#* *DB_DATABASE=.*/DB_DATABASE=${DB_DATABASE}/" .env
sed -i "s/^#* *DB_USERNAME=.*/DB_USERNAME=${DB_USERNAME}/" .env
sed -i "s/^#* *DB_PASSWORD=.*/DB_PASSWORD=${DB_PASSWORD}/" .env

cat .env | grep DB_

# atur kepemilikan dan hak akses direktori
echo "Mengatur kepemilikan dan hak akses direktori..."
chown -R $USER_ID:$GROUP_ID /var/www/html

# Memberikan akses penuh kepada user root komputer
echo "Memberikan akses penuh kepada user root komputer..."
chmod -R 755 /var/www/html

echo "Mengatur hak akses direktori storage dan bootstrap/cache..."
# Buat folder dan file log jika belum ada
mkdir -p /var/www/html/storage/logs
touch /var/www/html/storage/logs/laravel.log

# Memberikan akses tulis ke direktori storage dan bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Memberikan akses penuh kepada user root komputer untuk direktori penting
echo "Memberikan akses penuh untuk direktori penting..."
chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache

cd /var/www/html
echo "Membaca environment dari file .env..."

# Ambil APP_ENV dari .env file
APP_ENV=$(grep ^APP_ENV= .env | cut -d '=' -f2 | tr -d '\r')

if [[ -z "$APP_ENV" ]]; then
    echo "APP_ENV tidak ditemukan di file .env. Menggunakan 'local' sebagai default."
    APP_ENV=local
fi

echo "Environment Laravel: $APP_ENV"

echo "Menjalankan optimisasi Laravel..."
composer validate --strict
composer install --optimize-autoloader --no-dev

# Migrate database jika ada yang perlu 
# php artisan migrate:fresh
# Jalankan perintah artisan sesuai environment
# if [ "$APP_ENV" = "production" ]; then
#     echo "Mode production: menjalankan caching..."
#     php artisan config:clear
#     php artisan cache:clear
#     php artisan view:clear
#     php artisan route:clear

#     php artisan config:cache
#     php artisan route:cache
#     php artisan view:cache
# else
#     echo "Mode development: membersihkan cache..."
#     php artisan config:clear
#     php artisan cache:clear
#     php artisan view:clear
#     php artisan route:clear
# fi

# Jalankan Apache agar container tetap aktif
exec apache2-foreground