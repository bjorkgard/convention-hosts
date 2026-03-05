# Deployment Guide

This guide covers deploying your Laravel React application to production.

## Pre-Deployment Checklist

### Code Quality

- [ ] All tests passing: `composer test`
- [ ] Code linted: `composer lint:check` and `npm run lint:check`
- [ ] Code formatted: `npm run format:check`
- [ ] TypeScript checks: `npm run types:check`
- [ ] Run full CI check: `composer ci:check`

### Configuration

- [ ] Environment variables configured
- [ ] Database credentials set
- [ ] Mail service configured
- [ ] APP_DEBUG set to false
- [ ] APP_ENV set to production
- [ ] APP_KEY generated
- [ ] Proper file permissions

### Security

- [ ] HTTPS enabled
- [ ] CSRF protection enabled (default)
- [ ] Rate limiting configured
- [ ] Security headers configured
- [ ] Database backups scheduled

## Server Requirements

### Minimum Requirements

- PHP 8.2 or higher
- Composer 2.x
- Node.js 18+ and npm
- Web server (Nginx or Apache)
- Database (MySQL 8.0+, PostgreSQL 13+, or SQLite)
- SSL certificate

### PHP Extensions

Required extensions:
- BCMath
- Ctype
- cURL
- DOM
- Fileinfo
- JSON
- Mbstring
- OpenSSL
- PCRE
- PDO
- Tokenizer
- XML

### Recommended

- Redis for caching and queues
- Supervisor for queue workers
- CDN for static assets

## Environment Configuration

### Production .env

```env
# Application
APP_NAME="Convention Hosts"
APP_ENV=production
APP_KEY=base64:your-generated-key
APP_DEBUG=false
APP_URL=https://yourdomain.com
APP_TIMEZONE="Europe/Stockholm"

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_secure_password

# Cache & Session
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=587
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="${APP_NAME}"

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=error

# Vite
VITE_APP_NAME="${APP_NAME}"
```

## Deployment Steps

### 1. Server Setup

#### Install Dependencies

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP 8.2
sudo apt install php8.2 php8.2-fpm php8.2-cli php8.2-common \
    php8.2-mysql php8.2-xml php8.2-curl php8.2-mbstring \
    php8.2-zip php8.2-bcmath php8.2-redis -y

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install nodejs -y

# Install Nginx
sudo apt install nginx -y

# Install MySQL
sudo apt install mysql-server -y

# Install Redis
sudo apt install redis-server -y
```

### 2. Clone Repository

```bash
cd /var/www
sudo git clone <your-repo-url> your-app
cd your-app
sudo chown -R www-data:www-data /var/www/your-app
```

### 3. Install Application Dependencies

```bash
# Install PHP dependencies (production only)
composer install --optimize-autoloader --no-dev

# Install Node dependencies
npm ci

# Build frontend assets
npm run build
```

### 4. Configure Environment

```bash
# Copy environment file
cp .env.example .env

# Edit environment variables
nano .env

# Generate application key
php artisan key:generate
```

### 5. Set Up Database

```bash
# Run migrations
php artisan migrate --force

# Seed database (if needed)
php artisan db:seed --force
```

### 6. Optimize Application

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Cache events
php artisan event:cache
```

### 7. Set Permissions

```bash
# Set ownership
sudo chown -R www-data:www-data /var/www/your-app

# Set directory permissions
sudo find /var/www/your-app -type d -exec chmod 755 {} \;

# Set file permissions
sudo find /var/www/your-app -type f -exec chmod 644 {} \;

# Set storage and cache permissions
sudo chmod -R 775 /var/www/your-app/storage
sudo chmod -R 775 /var/www/your-app/bootstrap/cache
```

## Web Server Configuration

### Nginx Configuration

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com www.yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;
    root /var/www/your-app/public;

    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    index index.php;

    charset utf-8;

    # Logs
    access_log /var/log/nginx/your-app-access.log;
    error_log /var/log/nginx/your-app-error.log;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

Save to `/etc/nginx/sites-available/your-app`:

```bash
sudo nano /etc/nginx/sites-available/your-app
sudo ln -s /etc/nginx/sites-available/your-app /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### Apache Configuration

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    Redirect permanent / https://yourdomain.com/
</VirtualHost>

<VirtualHost *:443>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    DocumentRoot /var/www/your-app/public

    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/yourdomain.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/yourdomain.com/privkey.pem

    <Directory /var/www/your-app/public>
        AllowOverride All
        Require all granted
    </Directory>

    # Security Headers
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"

    ErrorLog ${APACHE_LOG_DIR}/your-app-error.log
    CustomLog ${APACHE_LOG_DIR}/your-app-access.log combined
</VirtualHost>
```

Enable required modules:

```bash
sudo a2enmod rewrite ssl headers
sudo systemctl restart apache2
```

## SSL Certificate

### Using Let's Encrypt (Certbot)

```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx -y

# Obtain certificate
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

# Auto-renewal (already configured)
sudo certbot renew --dry-run
```

## Queue Workers

### Supervisor Configuration

```bash
# Install Supervisor
sudo apt install supervisor -y

# Create configuration
sudo nano /etc/supervisor/conf.d/your-app-worker.conf
```

Add configuration:

```ini
[program:your-app-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/your-app/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/your-app/storage/logs/worker.log
stopwaitsecs=3600
```

Start worker:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start your-app-worker:*
```

## Scheduled Tasks

Add to crontab:

```bash
sudo crontab -e -u www-data
```

Add line:

```cron
* * * * * cd /var/www/your-app && php artisan schedule:run >> /dev/null 2>&1
```

## Database Backups

### Automated Backup Script

```bash
#!/bin/bash
# /usr/local/bin/backup-database.sh

BACKUP_DIR="/var/backups/mysql"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="your_database"
DB_USER="your_username"
DB_PASS="your_password"

mkdir -p $BACKUP_DIR

mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/backup_$DATE.sql.gz

# Keep only last 7 days
find $BACKUP_DIR -name "backup_*.sql.gz" -mtime +7 -delete
```

Make executable and schedule:

```bash
sudo chmod +x /usr/local/bin/backup-database.sh
sudo crontab -e
```

Add:

```cron
0 2 * * * /usr/local/bin/backup-database.sh
```

## Monitoring

### Application Monitoring

```bash
# View logs
tail -f storage/logs/laravel.log

# View Nginx logs
tail -f /var/log/nginx/your-app-error.log

# View queue worker logs
tail -f storage/logs/worker.log
```

### Health Check Endpoint

Create a health check route:

```php
// routes/web.php
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
    ]);
});
```

## Zero-Downtime Deployment

### Using Deployment Script

```bash
#!/bin/bash
# deploy.sh

set -e

echo "Starting deployment..."

# Pull latest code
git pull origin main

# Install dependencies
composer install --optimize-autoloader --no-dev
npm ci

# Build assets
npm run build

# Run migrations
php artisan migrate --force

# Clear and cache
php artisan config:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart queue workers
sudo supervisorctl restart your-app-worker:*

# Reload PHP-FPM
sudo systemctl reload php8.2-fpm

echo "Deployment completed!"
```

## Rollback Strategy

### Quick Rollback

```bash
# Revert to previous commit
git reset --hard HEAD~1

# Reinstall dependencies
composer install --optimize-autoloader --no-dev
npm ci
npm run build

# Rollback migrations
php artisan migrate:rollback

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart services
sudo supervisorctl restart your-app-worker:*
sudo systemctl reload php8.2-fpm
```

## Performance Optimization

### OPcache Configuration

```ini
; /etc/php/8.2/fpm/conf.d/10-opcache.ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.revalidate_freq=0
opcache.validate_timestamps=0
opcache.fast_shutdown=1
```

### Redis Configuration

```bash
# /etc/redis/redis.conf
maxmemory 256mb
maxmemory-policy allkeys-lru
```

## Troubleshooting

### Clear All Caches

```bash
php artisan optimize:clear
```

### Fix Permissions

```bash
sudo chown -R www-data:www-data /var/www/your-app
sudo chmod -R 775 storage bootstrap/cache
```

### Check Logs

```bash
# Application logs
tail -f storage/logs/laravel.log

# Web server logs
tail -f /var/log/nginx/your-app-error.log

# PHP-FPM logs
tail -f /var/log/php8.2-fpm.log
```

## Security Hardening

### Firewall Configuration

```bash
# Enable UFW
sudo ufw enable

# Allow SSH
sudo ufw allow 22

# Allow HTTP/HTTPS
sudo ufw allow 80
sudo ufw allow 443

# Check status
sudo ufw status
```

### Fail2Ban

```bash
# Install Fail2Ban
sudo apt install fail2ban -y

# Configure
sudo cp /etc/fail2ban/jail.conf /etc/fail2ban/jail.local
sudo systemctl enable fail2ban
sudo systemctl start fail2ban
```

## Next Steps

- Set up monitoring (e.g., Laravel Telescope, Sentry)
- Configure CDN for static assets
- Set up automated backups
- Implement log aggregation
- Configure application performance monitoring
