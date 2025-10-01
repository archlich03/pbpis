#!/bin/bash

# Wait for DB if needed (optional)
#sleep 5

# Laravel setup
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 777 /var/www/storage /var/www/bootstrap/cache

composer install
npm install

php artisan config:cache
php artisan route:cache
php artisan view:cache

# Only run Vite dev server in local development
if [ "$APP_ENV" = "local" ]; then
    echo "Starting Vite dev server..."
    npm run dev &
else
    echo "Production mode - using built assets"
fi

# Start PHP-FPM
exec php-fpm