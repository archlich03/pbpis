#!/bin/bash
set -e

echo "Production entrypoint starting..."

# Wait for database to be ready
echo "Waiting for database connection..."
until php artisan db:show 2>/dev/null; do
    echo "Database not ready, waiting..."
    sleep 2
done
echo "Database connected!"

# Run migrations with --force flag (required in production)
echo "Running migrations..."
php artisan migrate --force

# Create storage link if it doesn't exist
if [ ! -L /var/www/public/storage ]; then
    echo "Creating storage symlink..."
    php artisan storage:link
fi

# Cache configuration, routes, views, and events
echo "Caching configuration..."
php artisan config:cache
echo "Caching routes..."
php artisan route:cache
echo "Caching views..."
php artisan view:cache
echo "Caching events..."
php artisan event:cache

# Set proper permissions one more time
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 755 /var/www/storage /var/www/bootstrap/cache

echo "Starting supervisor..."
# Start supervisor which will manage PHP-FPM, Nginx, and Queue Worker
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
