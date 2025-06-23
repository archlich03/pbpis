#!/bin/bash

# Wait for DB if needed (optional)
sleep 5

# Laravel setup
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 777 /var/www/storage /var/www/bootstrap/cache
composer install
npm install
php artisan config:cache
php artisan route:cache
php artisan view:cache

npm run dev &

# Start PHP-FPM
exec php-fpm