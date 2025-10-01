#!/bin/sh
set -e

echo "🚀 Starting PBPIS Laravel Production Container..."

# Setup environment file
echo "🔧 Setting up environment configuration..."
if [ -f "/var/www/.env.production" ]; then
    cp /var/www/.env.production /var/www/.env
    echo "✅ Copied .env.production to .env"
else
    echo "⚠️ .env.production not found, using .env.example"
    cp /var/www/.env.example /var/www/.env
fi

# Generate APP_KEY if missing
if ! grep -q "^APP_KEY=base64:" /var/www/.env; then
    echo "🔑 Generating application key..."
    php artisan key:generate --force
    echo "✅ Application key generated"
fi

# Run Laravel optimizations for production
echo "⚡ Optimizing Laravel for production..."
# Clear any existing cached config first
php artisan config:clear
# Now cache the config with the proper .env values loaded
php artisan config:cache
php artisan route:cache
# Create view cache directory and run view cache
mkdir -p /var/www/storage/framework/views
chown -R www-data:www-data /var/www/storage/framework/views
chmod -R 755 /var/www/storage/framework/views
php artisan view:cache
php artisan event:cache

# Wait for database to be ready (with shorter timeout)
echo "⏳ Waiting for database connection..."
timeout=20
while ! php -r "try { new PDO('mysql:host=mysql;dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}', [PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false]); exit(0); } catch(Exception \$e) { exit(1); }"; do
    echo "Database not ready, waiting 3 seconds... ($timeout seconds remaining)"
    sleep 3
    timeout=$((timeout - 3))
    if [ $timeout -le 0 ]; then
        echo "⚠️ Database connection timeout. Starting web server anyway..."
        echo "💡 Database migrations can be run manually later with:"
        echo "   docker exec pbpis-prod php artisan migrate"
        break
    fi
done

# Run database migrations if database is available
if php -r "try { new PDO('mysql:host=mysql;dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}', [PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false]); exit(0); } catch(Exception \$e) { exit(1); }"; then
    echo "📊 Running database migrations..."
    php artisan migrate --force
else
    echo "⚠️ Skipping migrations - database not available yet"
fi

# Ensure proper permissions
echo "🔒 Setting file permissions..."
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 755 /var/www/storage /var/www/bootstrap/cache

# Create supervisor log directory
mkdir -p /var/log/supervisor

echo "✅ Laravel production setup complete!"

# Setup Laravel scheduler with cron
echo "📅 Setting up Laravel scheduler with cron..."
export TZ=Europe/Vilnius

# Clean up any existing cron processes and pid files
pkill crond 2>/dev/null || true
rm -f /var/run/crond.pid

# Add Laravel scheduler to crontab with console output
echo "TZ=Europe/Vilnius" > /tmp/crontab
echo "PATH=/usr/local/bin:/usr/bin:/bin" >> /tmp/crontab
echo "* * * * * cd /var/www && /usr/local/bin/php artisan schedule:run" >> /tmp/crontab
crontab /tmp/crontab

# Start cron daemon in background
crond -b

echo "🌐 Starting Nginx + PHP-FPM via Supervisor..."

# Start supervisor (which runs nginx + php-fpm)
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
