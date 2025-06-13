#!/bin/bash

# Wait for DB if needed (optional)
# sleep 10

# Laravel setup
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 777 /var/www/storage /var/www/bootstrap/cache
composer install
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan key:generate

# Start PHP-FPM
exec php-fpm