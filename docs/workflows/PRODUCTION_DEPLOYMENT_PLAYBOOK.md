# Production Deployment Playbook

Steps and considerations for deploying MuSoftware ERP to a production environment.

## 1. Environment Configuration
Duplicate the `.env.production` template to your server's `.env`.

### Critical Production Keys:
- `APP_ENV=production`
- `APP_DEBUG=false`
- `CACHE_STORE=redis`
- `QUEUE_CONNECTION=redis`
- `SESSION_DRIVER=redis`
- `MAIL_MAILER=mailgun` (or `ses`)

## 2. Database Preparation
Always run migrations securely.
```bash
php artisan migrate --force
```
*Note: Do not run seeders in production unless initializing a completely fresh instance.*

## 3. Daemon Services (Supervisor)
You must configure process monitors (like Supervisor) to keep the background workers alive.

**1. Queue Worker**
```ini
[program:musoftware-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/project/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
numprocs=4
```

**2. Reverb WebSocket Server**
```ini
[program:musoftware-reverb]
command=php /path/to/project/artisan reverb:start
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
```

## 4. CRON Scheduler
To enable the system to auto-invoice and auto-renew, the scheduler must run every minute.
Add to the server's crontab (`crontab -e`):
```cron
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

## 5. Performance Caching
Optimize the framework during deployment:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```
