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

# Set up Laravel scheduler cron job with full PHP path and PHP_BINARY env
echo "* * * * * cd /var/www && PHP_BINARY=/usr/local/bin/php /usr/local/bin/php artisan schedule:run >> /var/log/cron.log 2>&1" > /etc/cron.d/laravel-scheduler
chmod 0644 /etc/cron.d/laravel-scheduler
crontab /etc/cron.d/laravel-scheduler
touch /var/log/cron.log

# Start cron in background
echo "Starting cron daemon..."
cron

echo "Starting Vite dev server..."
npm run dev &

# Start PHP-FPM
exec php-fpm