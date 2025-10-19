# Production Deployment Guide

## Architecture Overview

The production setup uses a **single consolidated container** that runs:
- **PHP-FPM** (application server)
- **Nginx** (internal web server on port 80)
- **Queue Worker** (Laravel queue processor)

All three processes are managed by **Supervisor** within one container.

Your external Nginx instance will `proxy_pass` requests to this container on port 8000.

## Prerequisites

1. **Server with Docker and Docker Compose installed**
2. **External Nginx** configured with SSL
3. **Domain name** pointing to your server
4. **SSL certificate** (Let's Encrypt recommended)

## Initial Setup

### 1. Clone Repository on Production Server

```bash
cd /var/www
git clone <your-repo-url> pobis-prod
cd pobis-prod
```

### 2. Create Production Environment File

```bash
cp .env.production.example .env
```

### 3. Generate Secure Credentials

```bash
# Generate APP_KEY
php artisan key:generate --show

# Generate strong passwords for database and Redis
openssl rand -base64 32  # For DB_PASSWORD
openssl rand -base64 32  # For DB_ROOT_PASSWORD
openssl rand -base64 32  # For REDIS_PASSWORD
```

### 4. Edit .env File

```bash
nano .env
```

**Required changes:**
- Set `APP_KEY` (from step 3)
- Set `DB_PASSWORD` (strong password)
- Set `DB_ROOT_PASSWORD` (strong password)
- Set `REDIS_PASSWORD` (strong password)
- Set `DEFAULT_PASSWORD` (admin user password)
- Configure `MSGRAPH_CLIENT_ID` and `MSGRAPH_SECRET_ID` if using Microsoft OAuth
- Update `APP_URL` to your actual domain
- Configure SMTP settings if sending emails

### 5. Build and Start Services

```bash
# Build the production image
docker compose -f docker-compose.prod.yml build

# Start all services
docker compose -f docker-compose.prod.yml up -d

# Check status
docker compose -f docker-compose.prod.yml ps

# View logs
docker compose -f docker-compose.prod.yml logs -f app
```

### 6. Verify Application is Running

```bash
# Test internal nginx
curl http://localhost:8000

# Should return Laravel application HTML
```

## External Nginx Configuration

Configure your external Nginx to proxy requests to the container:

```nginx
server {
    listen 80;
    server_name pobis.teso.fyi;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name pobis.teso.fyi;

    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/pobis.teso.fyi/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/pobis.teso.fyi/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    # Security Headers
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Logging
    access_log /var/log/nginx/pobis_access.log;
    error_log /var/log/nginx/pobis_error.log;

    # Proxy to Docker container
    location / {
        proxy_pass http://localhost:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        
        # Timeouts
        proxy_connect_timeout 300;
        proxy_send_timeout 300;
        proxy_read_timeout 300;
        
        # Buffer settings
        proxy_buffering off;
        proxy_request_buffering off;
    }

    # Increase max upload size
    client_max_body_size 100M;
}
```

**Apply configuration:**
```bash
sudo nginx -t
sudo systemctl reload nginx
```

## Post-Deployment Tasks

### 1. Seed Initial Data (if needed)

```bash
docker compose -f docker-compose.prod.yml exec app php artisan db:seed
```

### 2. Create Admin User

The default admin user is created automatically with credentials from `.env`:
- Email: `DEFAULT_EMAIL`
- Password: `DEFAULT_PASSWORD`

### 3. Configure Microsoft OAuth (if using)

1. Register application in Azure AD
2. Add redirect URI: `https://pobis.teso.fyi/login/microsoft/callback`
3. Update `.env` with client ID and secret
4. Restart container:
   ```bash
   docker compose -f docker-compose.prod.yml restart app
   ```

## Maintenance Commands

### View Logs
```bash
# All logs
docker compose -f docker-compose.prod.yml logs -f

# App only
docker compose -f docker-compose.prod.yml logs -f app

# Last 100 lines
docker compose -f docker-compose.prod.yml logs --tail=100 app
```

### Restart Services
```bash
# Restart all
docker compose -f docker-compose.prod.yml restart

# Restart app only
docker compose -f docker-compose.prod.yml restart app
```

### Run Artisan Commands
```bash
docker compose -f docker-compose.prod.yml exec app php artisan <command>

# Examples:
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
docker compose -f docker-compose.prod.yml exec app php artisan cache:clear
docker compose -f docker-compose.prod.yml exec app php artisan queue:restart
```

### Access Container Shell
```bash
docker compose -f docker-compose.prod.yml exec app bash
```

### Database Backup
```bash
# Create backup
docker compose -f docker-compose.prod.yml exec mysql mysqldump -u root -p${DB_ROOT_PASSWORD} pobis_prod > backup_$(date +%Y%m%d_%H%M%S).sql

# Restore backup
docker compose -f docker-compose.prod.yml exec -T mysql mysql -u root -p${DB_ROOT_PASSWORD} pobis_prod < backup.sql
```

## Updating Application

### 1. Pull Latest Code
```bash
cd /var/www/pobis-prod
git pull origin main
```

### 2. Rebuild and Restart
```bash
# Rebuild image with new code
docker compose -f docker-compose.prod.yml build

# Restart services
docker compose -f docker-compose.prod.yml up -d

# Migrations will run automatically via entrypoint
```

### 3. Clear Caches (if needed)
```bash
docker compose -f docker-compose.prod.yml exec app php artisan cache:clear
docker compose -f docker-compose.prod.yml exec app php artisan config:cache
docker compose -f docker-compose.prod.yml exec app php artisan route:cache
docker compose -f docker-compose.prod.yml exec app php artisan view:cache
```

## Monitoring

### Check Container Health
```bash
docker compose -f docker-compose.prod.yml ps
```

### Check Supervisor Status (inside container)
```bash
docker compose -f docker-compose.prod.yml exec app supervisorctl status
```

### Monitor Queue Jobs
```bash
docker compose -f docker-compose.prod.yml exec app php artisan queue:monitor
```

## Troubleshooting

### Container Won't Start
```bash
# Check logs
docker compose -f docker-compose.prod.yml logs app

# Common issues:
# - Database connection failed: Check DB credentials in .env
# - Redis connection failed: Check REDIS_PASSWORD in .env
# - Permission errors: Check storage/ directory permissions
```

### Application Returns 500 Error
```bash
# Check Laravel logs
docker compose -f docker-compose.prod.yml exec app tail -f storage/logs/laravel.log

# Check nginx error log
docker compose -f docker-compose.prod.yml exec app tail -f /var/log/nginx/error.log
```

### Queue Jobs Not Processing
```bash
# Check queue worker status
docker compose -f docker-compose.prod.yml exec app supervisorctl status queue-worker

# Restart queue worker
docker compose -f docker-compose.prod.yml exec app supervisorctl restart queue-worker

# View queue worker logs
docker compose -f docker-compose.prod.yml logs -f app | grep queue-worker
```

### Database Connection Issues
```bash
# Test database connection
docker compose -f docker-compose.prod.yml exec app php artisan db:show

# Check MySQL is running
docker compose -f docker-compose.prod.yml ps mysql

# Check MySQL logs
docker compose -f docker-compose.prod.yml logs mysql
```

### Redis Connection Issues
```bash
# Test Redis connection
docker compose -f docker-compose.prod.yml exec redis redis-cli -a ${REDIS_PASSWORD} ping

# Should return: PONG
```

## Security Checklist

- [ ] `APP_DEBUG=false` in production .env
- [ ] Strong passwords for DB_PASSWORD, DB_ROOT_PASSWORD, REDIS_PASSWORD
- [ ] APP_KEY generated and unique
- [ ] SSL certificate installed and configured
- [ ] External Nginx configured with security headers
- [ ] Firewall configured (only ports 80, 443, 22 open)
- [ ] Regular backups scheduled
- [ ] Log monitoring configured
- [ ] Microsoft OAuth redirect URIs registered correctly

## Backup Strategy

### Automated Daily Backups

Create a cron job:
```bash
sudo crontab -e
```

Add:
```cron
# Daily database backup at 2 AM
0 2 * * * cd /var/www/pobis-prod && docker compose -f docker-compose.prod.yml exec -T mysql mysqldump -u root -p${DB_ROOT_PASSWORD} pobis_prod | gzip > /backups/pobis_$(date +\%Y\%m\%d).sql.gz

# Keep only last 30 days
0 3 * * * find /backups -name "pobis_*.sql.gz" -mtime +30 -delete
```

### Storage Volume Backup
```bash
# Backup uploaded files
tar -czf storage_backup_$(date +%Y%m%d).tar.gz ./storage/app
```

## Performance Optimization

### Enable OPcache (already configured in Dockerfile.prod)
- `opcache.enable=1`
- `opcache.memory_consumption=256`
- `opcache.max_accelerated_files=20000`
- `opcache.validate_timestamps=0`

### Redis Caching (already configured)
- Session storage: Redis
- Cache driver: Redis
- Queue driver: Database (with Redis as option)

### Database Optimization
```bash
# Optimize tables
docker compose -f docker-compose.prod.yml exec mysql mysqlcheck -u root -p${DB_ROOT_PASSWORD} --optimize pobis_prod
```

## Scaling Considerations

If you need to scale:

1. **Separate Queue Workers**: Create additional containers running only queue workers
2. **Load Balancer**: Add multiple app containers behind your external Nginx
3. **External Redis**: Move Redis to separate server/cluster
4. **External MySQL**: Move MySQL to managed database service
5. **CDN**: Serve static assets via CDN

## Support

For issues or questions:
1. Check logs: `docker compose -f docker-compose.prod.yml logs -f`
2. Review Laravel logs: `storage/logs/laravel.log`
3. Check supervisor status: `supervisorctl status`
