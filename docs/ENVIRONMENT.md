# Environment Configuration Guide (`.env`)

## Overview

The ERP System relies on environmental variables stored inside the `.env` file at the project root to manage database connections, cache drivers, mailer credentials, external API keys, and Reverb WebSocket server configurations.

## Master Production `.env` Blueprint

```ini
# Application Identity & Mode
APP_NAME="MuSoftware ERP"
APP_ENV=production
APP_KEY=base64:7BvJ9Gk...
APP_DEBUG=false
APP_URL=https://erp.musoftwares.com
APP_TIMEZONE="UTC"
APP_LOCALE="en"

# Session & Authentication Security
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_PATH=/
SESSION_DOMAIN=".musoftwares.com"
SESSION_SECURE_COOKIE=true

# Database Engine Setup
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=musoftware_erp_prod
DB_USERNAME=db_user_prod
DB_PASSWORD="ComplexSecurePassword99!"
DB_COLLATION=utf8mb4_unicode_ci

# Cache, Queue & Broadcasting Drivers
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
BROADCAST_DRIVER=reverb

# Redis Connection Details
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_PREFIX="erp_sys_"

# Mail Delivery Setup (SMTP via SendGrid / SES)
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=SG.xxxxxx.yyyyyy
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@musoftwares.com
MAIL_FROM_NAME="MuSoftware ERP Billing"

# File Storage Configuration
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=AKIAIOSFODNN7EXAMPLE
AWS_SECRET_ACCESS_KEY=wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=musoftware-erp-assets
AWS_USE_PATH_STYLE_ENDPOINT=false

# Reverb WebSocket Configuration
REVERB_APP_ID=881292
REVERB_APP_KEY=reverb_secret_key_99
REVERB_APP_SECRET=reverb_secure_secret_token_102
REVERB_HOST="erp.musoftwares.com"
REVERB_PORT=8080
REVERB_SCHEME=https

# Frontend Asset Injection Matching Reverb Variables
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"

# External Services
EXCHANGE_RATE_API_KEY="ex_api_9918201"
```

## Security Best Practices

### 1. Protect `.env` from Public Web Access
Nginx must strictly deny access to `.env` or any file beginning with a dot (`.`).

```nginx
location ~ /\. {
    deny all;
}
```

### 2. Never Commit `.env` to Version Control
Ensure `.env` is explicitly listed in `.gitignore`. Only commit `.env.example` with blank or dummy values.

### 3. Clear Config Caches After `.env` Updates
Whenever you modify a value in your `.env` file on a staging or production server, you must run:

```bash
php artisan config:cache
```
If you do not execute this command, Laravel workers will continue using old cached configuration values in memory.
