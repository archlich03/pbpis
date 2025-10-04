#!/bin/bash

# Wait for DB if needed (optional)
#sleep 5

# Laravel setup
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 777 /var/www/storage /var/www/bootstrap/cache

composer install
npm install

echo "Development mode (APP_ENV=${APP_ENV:-not set}) - clearing caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
echo "Starting Vite dev server..."
npm run dev &

# Start PHP-FPM
exec php-fpm