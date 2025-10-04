#!/bin/bash

# Wait for DB if needed (optional)
#sleep 5

# Laravel setup
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 777 /var/www/storage /var/www/bootstrap/cache

composer install
npm install

# Only cache in production, clear caches in development
if [ "$APP_ENV" = "local" ]; then
    echo "Development mode - clearing caches..."
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    echo "Starting Vite dev server..."
    npm run dev &
else
    echo "Production mode - caching and using built assets"
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

# Start PHP-FPM
exec php-fpm