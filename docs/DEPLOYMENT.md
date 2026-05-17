# Production Deployment & Operations Guide

## 1. Server & Infrastructure Requirements

The ERP System is optimized to run on standard Linux cloud server instances (such as DigitalOcean Droplets, AWS EC2, or Hetzner Cloud).

### Minimum Hardware Specs (Up to 100 Active Tenants)
- **CPU:** 2 vCPU
- **RAM:** 4 GB DDR4+
- **Storage:** 50 GB NVMe SSD
- **Operating System:** Ubuntu 22.04 LTS or 24.04 LTS

### Software Stack Architecture
- **Web Server:** Nginx (Acts as reverse proxy for PHP-FPM and WebSocket server)
- **PHP Runtime:** PHP 8.2 or 8.3 FPM with OPcache enabled
- **Database Engine:** MySQL 8.0+ or Percona Server
- **In-Memory Cache & Queues:** Redis 7.0+
- **Process Manager:** Supervisor 4.x
- **SSL Automation:** Let's Encrypt via Certbot

---

## 2. Server Installation & Provisioning Steps

```bash
# 1. Update system packages
sudo apt update && sudo apt upgrade -y

# 2. Install essential utilities and Redis
sudo apt install -y curl zip unzip git supervisor nginx redis-server software-properties-common

# 3. Add PHP repository and install PHP 8.2
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-xml php8.2-bcmath php8.2-curl php8.2-zip php8.2-gd php8.2-intl

# 4. Install Node.js 20.x
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# 5. Install Composer globally
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

---

## 3. Cloning Code & Compiling Production Builds

```bash
# Clone repository into web directory
sudo mkdir -p /var/www/erp-system
sudo chown -R $USER:$USER /var/www/erp-system
git clone https://github.com/musoftware/erp-system.git /var/www/erp-system
cd /var/www/erp-system

# Install PHP production dependencies
composer install --optimize-autoloader --no-dev

# Setup environment file
cp .env.example .env
php artisan key:generate

# Build frontend production bundle
npm install
npm run build

# Set directory permissions
sudo chown -R www-data:www-data /var/www/erp-system/storage /var/www/erp-system/bootstrap/cache
sudo chmod -R 775 /var/www/erp-system/storage /var/www/erp-system/bootstrap/cache
```

---

## 4. Database Optimization & Migrations

```bash
# Run production database migrations
php artisan migrate --force

# Seed production exchange rates and default currencies
php artisan db:seed --force
```

---

## 5. Supervisor Configuration (Queues & WebSockets)

To maintain robust background job execution and keep the Laravel Reverb WebSocket daemon alive permanently, configure Supervisor. Create a config file at `/etc/supervisor/conf.d/erp-system.conf`:

```ini
[program:erp-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/erp-system/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/erp-system/storage/logs/worker.log
stopwaitsecs=3600

[program:erp-reverb-daemon]
command=php /var/www/erp-system/artisan reverb:start --host=127.0.0.1 --port=8080
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/erp-system/storage/logs/reverb.log

[program:erp-scheduler]
command=/bin/bash -c "while true; do php /var/www/erp-system/artisan schedule:run >> /dev/null 2>&1; sleep 60; done"
autostart=true
autorestart=true
user=www-data
```

Activate supervisor tasks:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start all
```

---

## 6. Nginx Production Configuration

Create an Nginx server block at `/etc/nginx/sites-available/erp-system`:

```nginx
server {
    listen 80;
    server_name erp.musoftwares.com;
    root /var/www/erp-system/public;
    index index.php index.html;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-XSS-Protection "1; mode=block";
    add_header X-Content-Type-Options "nosniff";

    charset utf-8;

    # Standard Application Routing
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Proxy Reverb WebSocket Traffic
    location /app/ {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable site and configure SSL:
```bash
sudo ln -s /etc/nginx/sites-available/erp-system /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx

# Install Let's Encrypt SSL
sudo apt install certbot python3-certbot-nginx -y
sudo certbot --nginx -d erp.musoftwares.com
```

---

## 7. Production Caching & Optimization

```bash
# Cache configuration, routes, and views for maximum execution speed
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```
