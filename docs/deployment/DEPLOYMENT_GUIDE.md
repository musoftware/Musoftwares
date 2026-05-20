# Musoftware Platform — Deployment & Infrastructure Guide

> **Type**: Production Deployment Blueprint  
> **Stack**: Laravel 12 + Node.js Runtime + SQLite/MySQL + MeiliSearch

---

## 1. Platform Architecture (Production)

```
                          ┌─────────────────────────────────┐
                          │     Load Balancer / Nginx        │
                          │   (SSL termination, static files) │
                          └────────────┬────────────────────┘
                                       │
                          ┌────────────▼────────────────────┐
                          │    PHP-FPM / Laravel App         │
                          │    (Multiple instances via PM2   │
                          │     or Docker containers)        │
                          └────┬──────────┬─────────────────┘
                               │          │
              ┌────────────────▼──┐   ┌───▼──────────────────┐
              │  MySQL / MariaDB  │   │  Redis                │
              │  (Primary DB)     │   │  (Sessions + Queues + │
              └───────────────────┘   │   Cache)              │
                                      └──────────────────────┘
                          ┌───────────────────────────────┐
                          │  Laravel Queue Worker(s)       │
                          │  (Laravel Horizon recommended) │
                          └───────────────────────────────┘
                          ┌───────────────────────────────┐
                          │  MeiliSearch                   │
                          │  (Full-text search engine)     │
                          └───────────────────────────────┘
                          ┌───────────────────────────────┐
                          │  AWS S3 (or compatible)        │
                          │  (File storage)                │
                          └───────────────────────────────┘
                          ┌───────────────────────────────┐
                          │  Pusher / Laravel Reverb       │
                          │  (WebSocket broadcasting)      │
                          └───────────────────────────────┘

User's Machine:
  ┌─────────────────────────────────────────────────────────┐
  │  musoftware-runtime.exe (compiled)                       │
  │  HTTP: 127.0.0.1:18400                                   │
  │  WS: 127.0.0.1:18401                                     │
  └─────────────────────────────────────────────────────────┘
```

---

## 2. Environment Configuration

### Platform `.env` (Production)

```bash
# Application
APP_NAME="Musoftware"
APP_ENV=production
APP_KEY=base64:{generated_key}
APP_DEBUG=false
APP_URL=https://musoftware.com

# Database (Switch from SQLite to MySQL)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=musoftware
DB_USERNAME=musoftware_user
DB_PASSWORD={secure_password}

# Redis (Required for production)
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD={redis_password}

# Cache + Session + Queue → Redis
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Broadcasting (choose one)
BROADCAST_CONNECTION=reverb
# OR for Pusher:
# PUSHER_APP_ID=...
# PUSHER_APP_KEY=...
# PUSHER_APP_SECRET=...
# PUSHER_HOST=...

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME={mailgun_user}
MAIL_PASSWORD={mailgun_password}
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@musoftware.com
MAIL_FROM_NAME="${APP_NAME}"

# AWS S3
AWS_ACCESS_KEY_ID={key}
AWS_SECRET_ACCESS_KEY={secret}
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=musoftware-uploads
FILESYSTEM_DISK=s3

# MeiliSearch
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY={meilisearch_master_key}

# Kashier Payment Gateway
KASHIER_MERCHANT_ID={merchant_id}
KASHIER_API_KEY={api_key}
KASHIER_IFRAME_KEY={iframe_key}
KASHIER_MODE=production   # or 'test' for staging

# ERP Config
ERP_REFERRAL_COMMISSION_L1=5   # percentage
```

### Runtime Agent `.env`

```bash
# musoftware-runtime/.env
PLATFORM_URL=https://musoftware.com
HTTP_PORT=18400
WS_PORT=18401
PLUGINS_DIR=./plugins
UPDATE_CHANNEL=stable
PYTHON_BIN=python3
LOG_LEVEL=info
```

---

## 3. Server Setup (Ubuntu 22.04)

### 3.1 PHP + Laravel

```bash
# PHP 8.2 + extensions
sudo apt install php8.2 php8.2-fpm php8.2-mysql php8.2-redis \
  php8.2-mbstring php8.2-xml php8.2-curl php8.2-zip php8.2-gd \
  php8.2-bcmath php8.2-sqlite3

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Clone + setup
git clone {repo} /var/www/musoftware
cd /var/www/musoftware
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate

# Permissions
sudo chown -R www-data:www-data /var/www/musoftware/storage
sudo chown -R www-data:www-data /var/www/musoftware/bootstrap/cache
sudo chmod -R 775 /var/www/musoftware/storage

# Run migrations
php artisan migrate --force

# Build frontend assets
npm install && npm run build

# Optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan scout:import "App\Models\User"
php artisan scout:import "Modules\ERP\Models\Invoice"
```

### 3.2 Nginx Configuration

```nginx
server {
    listen 80;
    server_name musoftware.com www.musoftware.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name musoftware.com www.musoftware.com;

    ssl_certificate /etc/letsencrypt/live/musoftware.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/musoftware.com/privkey.pem;

    root /var/www/musoftware/public;
    index index.php;

    # Static files with caching
    location ~* \.(js|css|png|jpg|jpeg|gif|svg|ico|woff|woff2)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # PHP-FPM
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    # Max file upload size (for tool version uploads)
    client_max_body_size 500M;
}
```

### 3.3 MySQL Setup

```sql
CREATE DATABASE musoftware CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'musoftware_user'@'localhost' IDENTIFIED BY '{password}';
GRANT ALL PRIVILEGES ON musoftware.* TO 'musoftware_user'@'localhost';
FLUSH PRIVILEGES;
```

### 3.4 Queue Worker (Supervisor)

```ini
; /etc/supervisor/conf.d/musoftware-worker.conf
[program:musoftware-worker]
command=php /var/www/musoftware/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
directory=/var/www/musoftware
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/musoftware/storage/logs/worker.log
stopwaitsecs=3600
```

### 3.5 Laravel Scheduler (Crontab)

```bash
# Add to www-data crontab:
* * * * * php /var/www/musoftware/artisan schedule:run >> /dev/null 2>&1
```

### 3.6 MeiliSearch

```bash
# Install
curl -L https://install.meilisearch.com | sh
sudo mv meilisearch /usr/local/bin/

# Systemd service
sudo tee /etc/systemd/system/meilisearch.service << EOF
[Unit]
Description=MeiliSearch
After=network.target

[Service]
ExecStart=/usr/local/bin/meilisearch \
  --env production \
  --master-key {meilisearch_master_key} \
  --db-path /var/lib/meilisearch/data.ms \
  --http-addr 127.0.0.1:7700
User=meilisearch
Group=meilisearch
Restart=on-failure

[Install]
WantedBy=multi-user.target
EOF

sudo systemctl enable meilisearch
sudo systemctl start meilisearch
```

---

## 4. Runtime Agent Distribution

### Build Compiled Binaries

```bash
cd newmusoftwareTools/musoftware-runtime
npm install
npm run build:win   # → dist/musoftware-runtime-win.exe
npm run build:mac   # → dist/musoftware-runtime-mac
npm run build:linux # → dist/musoftware-runtime-linux
```

### Upload to Platform

```bash
# Upload to public downloads directory
scp dist/musoftware-runtime-win.exe user@server:/var/www/musoftware/public/downloads/runtime/

# Create version manifest
cat > /var/www/musoftware/public/downloads/runtime/latest.json << EOF
{
  "version": "1.0.0",
  "minimum_supported": "1.0.0",
  "channel": "stable",
  "downloads": {
    "windows": "https://musoftware.com/downloads/runtime/musoftware-runtime-win.exe",
    "mac": "https://musoftware.com/downloads/runtime/musoftware-runtime-mac",
    "linux": "https://musoftware.com/downloads/runtime/musoftware-runtime-linux"
  },
  "changelog": [
    "Initial release"
  ]
}
EOF
```

### Runtime Auto-Update Flow
```
Runtime polls: GET /api/runtime/version
  └── Compares current version with latest
  └── If update available → downloads new binary
  └── Replaces itself + restarts (platform-specific)
```

---

## 5. Docker Compose (Dev/Staging)

```yaml
# docker-compose.testing.yml (exists in repo)
version: '3.8'

services:
  app:
    build: .
    volumes:
      - .:/var/www/html
    depends_on:
      - mysql
      - redis
      - meilisearch

  mysql:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: musoftware_test
      MYSQL_USER: test_user
      MYSQL_PASSWORD: test_password
      MYSQL_ROOT_PASSWORD: root

  redis:
    image: redis:7-alpine

  meilisearch:
    image: getmeili/meilisearch:latest
    environment:
      MEILI_ENV: development
```

---

## 6. CI/CD Pipeline (GitHub Actions)

```yaml
# .github/workflows/deploy.yml (recommended)
name: Deploy to Production

on:
  push:
    branches: [main]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Run Tests
        run: |
          composer install
          php artisan test
          npm install && npm run build
      
      - name: Deploy via SSH
        uses: appleboy/ssh-action@master
        with:
          host: ${{ secrets.DEPLOY_HOST }}
          script: |
            cd /var/www/musoftware
            git pull origin main
            composer install --no-dev --optimize-autoloader
            npm install && npm run build
            php artisan migrate --force
            php artisan config:cache
            php artisan route:cache
            php artisan view:cache
            sudo supervisorctl restart musoftware-worker
```

---

## 7. Production Deployment Checklist

### Pre-Launch
- [ ] Switch DB_CONNECTION from sqlite to mysql
- [ ] Configure Redis (QUEUE_CONNECTION=redis, SESSION_DRIVER=redis)
- [ ] Configure mail provider (Mailgun/SES)
- [ ] Configure S3 bucket + IAM credentials
- [ ] Configure Kashier production credentials (KASHIER_MODE=production)
- [ ] Configure MeiliSearch with master key
- [ ] Configure broadcasting (Reverb or Pusher)
- [ ] Generate production APP_KEY
- [ ] Enable HTTPS (Let's Encrypt)
- [ ] Configure Supervisor for queue workers
- [ ] Add scheduler to crontab
- [ ] Upload runtime binaries to /public/downloads/runtime/
- [ ] Create /public/downloads/runtime/latest.json manifest

### Post-Launch
- [ ] Verify email delivery
- [ ] Test Kashier payment flow in production mode
- [ ] Test S3 file upload (KYC documents)
- [ ] Test MeiliSearch indexing
- [ ] Test runtime binary download and installation
- [ ] Test WebSocket broadcasting
- [ ] Monitor queue worker logs
- [ ] Set up uptime monitoring (Uptime Robot, Better Uptime, etc.)
- [ ] Configure log aggregation (Papertrail, Datadog, etc.)

---

## 8. Admin Setup (First Run)

```bash
# Create admin user
php artisan tinker
>>> $user = App\Models\User::create([
...   'name' => 'Admin',
...   'email' => 'admin@musoftware.com',
...   'password' => bcrypt('secure_password'),
...   'onboarding_completed' => true,
... ]);
>>> $user->assignRole('admin');

# Create site settings (currencies, referral rates, etc.)
# Use admin panel after login

# Create first module plans (required for subscription system)
>>> Modules\ERP\Models\ModulePlan::create([
...   'name' => 'ERP Basic',
...   'module' => 'erp',
...   'billing' => 'monthly',
...   'price' => 29.99,
...   'currency' => 'USD',
...   'features' => ['Unlimited clients', 'Invoice management', 'Task tracking'],
...   'is_active' => true,
...   'sort_order' => 1,
... ]);
```
