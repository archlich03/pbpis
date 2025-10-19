# External Nginx Configuration for POBIS

## Quick Setup

This configuration goes on your **host machine's Nginx** (not inside Docker).

### 1. Create Nginx Site Configuration

```bash
sudo nano /etc/nginx/sites-available/pobis
```

### 2. Paste This Configuration

```nginx
# HTTP - Redirect to HTTPS
server {
    listen 80;
    listen [::]:80;
    server_name pobis.teso.fyi;
    
    # Let's Encrypt ACME challenge
    location /.well-known/acme-challenge/ {
        root /var/www/letsencrypt;
    }
    
    # Redirect all other traffic to HTTPS
    location / {
        return 301 https://$server_name$request_uri;
    }
}

# HTTPS - Main Application
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name pobis.teso.fyi;

    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/pobis.teso.fyi/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/pobis.teso.fyi/privkey.pem;
    
    # SSL Security Settings
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers 'ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384';
    ssl_prefer_server_ciphers off;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;
    ssl_stapling on;
    ssl_stapling_verify on;
    
    # Security Headers
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    
    # Logging
    access_log /var/log/nginx/pobis_access.log;
    error_log /var/log/nginx/pobis_error.log warn;

    # Max upload size
    client_max_body_size 100M;
    client_body_timeout 300s;

    # Proxy to Docker container
    location / {
        # Container is listening on localhost:8000
        proxy_pass http://127.0.0.1:8000;
        
        # Preserve original request information
        # CRITICAL: Set Host to actual domain, not $host variable
        proxy_set_header Host pobis.teso.fyi;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Forwarded-Host pobis.teso.fyi;
        proxy_set_header X-Forwarded-Port $server_port;
        
        # WebSocket support (if needed in future)
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        
        # Timeouts
        proxy_connect_timeout 300s;
        proxy_send_timeout 300s;
        proxy_read_timeout 300s;
        
        # Buffering
        proxy_buffering off;
        proxy_request_buffering off;
        
        # Redirect handling
        proxy_redirect off;
    }
}
```

### 3. Enable Site

```bash
# Create symbolic link
sudo ln -s /etc/nginx/sites-available/pobis /etc/nginx/sites-enabled/

# Test configuration
sudo nginx -t

# Reload Nginx
sudo systemctl reload nginx
```

## SSL Certificate Setup (Let's Encrypt)

### Option A: Using Certbot (Recommended)

```bash
# Install Certbot
sudo apt update
sudo apt install certbot python3-certbot-nginx

# Obtain certificate (will auto-configure Nginx)
sudo certbot --nginx -d pobis.teso.fyi

# Test auto-renewal
sudo certbot renew --dry-run
```

### Option B: Manual Certificate

If you already have certificates, place them in:
- Certificate: `/etc/letsencrypt/live/pobis.teso.fyi/fullchain.pem`
- Private Key: `/etc/letsencrypt/live/pobis.teso.fyi/privkey.pem`

## Firewall Configuration

```bash
# Allow HTTP and HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Allow SSH (if not already allowed)
sudo ufw allow 22/tcp

# Enable firewall
sudo ufw enable

# Check status
sudo ufw status
```

## Testing

### 1. Test HTTP to HTTPS Redirect
```bash
curl -I http://pobis.teso.fyi
# Should return: 301 Moved Permanently
```

### 2. Test HTTPS Connection
```bash
curl -I https://pobis.teso.fyi
# Should return: 200 OK
```

### 3. Test SSL Configuration
```bash
# Using SSL Labs (online)
# Visit: https://www.ssllabs.com/ssltest/analyze.html?d=pobis.teso.fyi

# Or using testssl.sh (local)
docker run --rm -ti drwetter/testssl.sh pobis.teso.fyi
```

### 4. Test Application
```bash
# Visit in browser
https://pobis.teso.fyi
```

## Monitoring

### View Access Logs
```bash
sudo tail -f /var/log/nginx/pobis_access.log
```

### View Error Logs
```bash
sudo tail -f /var/log/nginx/pobis_error.log
```

### Check Nginx Status
```bash
sudo systemctl status nginx
```

## Troubleshooting

### 502 Bad Gateway
- **Cause**: Docker container not running or not accessible on port 8000
- **Fix**: 
  ```bash
  # Check container is running
  docker compose -f docker-compose.prod.yml ps
  
  # Test container directly
  curl http://localhost:8000
  ```

### 504 Gateway Timeout
- **Cause**: Application taking too long to respond
- **Fix**: Increase timeouts in nginx config (already set to 300s)

### SSL Certificate Errors
- **Cause**: Certificate expired or not found
- **Fix**: 
  ```bash
  # Check certificate expiry
  sudo certbot certificates
  
  # Renew if needed
  sudo certbot renew
  ```

### Connection Refused
- **Cause**: Nginx not running or firewall blocking
- **Fix**:
  ```bash
  # Check Nginx is running
  sudo systemctl status nginx
  
  # Check firewall
  sudo ufw status
  ```

## Performance Tuning

### Enable Gzip Compression

Add to `/etc/nginx/nginx.conf` in the `http` block:

```nginx
# Gzip Settings
gzip on;
gzip_vary on;
gzip_proxied any;
gzip_comp_level 6;
gzip_types text/plain text/css text/xml text/javascript application/json application/javascript application/xml+rss application/rss+xml font/truetype font/opentype application/vnd.ms-fontobject image/svg+xml;
gzip_disable "msie6";
```

### Enable Caching for Static Assets

Add to your server block:

```nginx
# Cache static assets
location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
    proxy_pass http://127.0.0.1:8000;
    proxy_cache_valid 200 1y;
    expires 1y;
    add_header Cache-Control "public, immutable";
    access_log off;
}
```

### Rate Limiting

Add to `/etc/nginx/nginx.conf` in the `http` block:

```nginx
# Rate limiting
limit_req_zone $binary_remote_addr zone=general:10m rate=10r/s;
limit_req_zone $binary_remote_addr zone=login:10m rate=5r/m;
```

Then in your server block:

```nginx
# Apply rate limiting to login endpoints
location ~ ^/(login|register) {
    limit_req zone=login burst=5 nodelay;
    proxy_pass http://127.0.0.1:8000;
    # ... other proxy settings
}

# General rate limiting
location / {
    limit_req zone=general burst=20 nodelay;
    proxy_pass http://127.0.0.1:8000;
    # ... other proxy settings
}
```

## Maintenance

### Reload Nginx (without downtime)
```bash
sudo nginx -t && sudo systemctl reload nginx
```

### Restart Nginx
```bash
sudo systemctl restart nginx
```

### View Nginx Configuration
```bash
sudo nginx -T
```

## Security Best Practices

- ✅ HTTPS enforced (HTTP redirects to HTTPS)
- ✅ Modern TLS protocols only (1.2 and 1.3)
- ✅ Security headers configured
- ✅ HSTS enabled with preload
- ✅ SSL stapling enabled
- ✅ Rate limiting configured
- ✅ Large upload size allowed (100M)
- ✅ Firewall configured

## Quick Commands Reference

```bash
# Test Nginx config
sudo nginx -t

# Reload Nginx
sudo systemctl reload nginx

# Restart Nginx
sudo systemctl restart nginx

# View logs
sudo tail -f /var/log/nginx/pobis_access.log
sudo tail -f /var/log/nginx/pobis_error.log

# Renew SSL certificate
sudo certbot renew

# Check SSL certificate
sudo certbot certificates

# Test container connection
curl http://localhost:8000
```
