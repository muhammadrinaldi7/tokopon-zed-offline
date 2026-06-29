#!/bin/sh

# Jalankan optimasi Laravel tepat saat kontainer menyala
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Jalankan proses utama PHP-FPM (meneruskan perintah bawaan Docker)
exec php-fpm