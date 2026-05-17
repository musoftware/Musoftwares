# Maintenance & Operations Routine

## 1. Automated & Manual Database Backups

Because the ERP System manages immutable financial ledgers, automated daily database backups are mandatory.

### Scripted MySQL Dump Routine
Create a shell backup script at `/usr/local/bin/backup_erp.sh`:

```bash
#!/bin/bash
BACKUP_DIR="/var/backups/erp_mysql"
DATE=$(date +"%Y%m%d_%H%M%S")
DB_NAME="musoftware_erp_prod"
DB_USER="db_user_prod"
DB_PASS="ComplexSecurePassword99!"

mkdir -p $BACKUP_DIR

# Dump database and compress
mysqldump --user=$DB_USER --password=$DB_PASS --single-transaction --quick --lock-tables=false $DB_NAME | gzip > "$BACKUP_DIR/db_backup_$DATE.sql.gz"

# Delete backups older than 30 days
find $BACKUP_DIR -type f -name "*.sql.gz" -mtime +30 -exec rm {} \;

# Optional: Sync backup archive to AWS S3 bucket
# aws s3 cp "$BACKUP_DIR/db_backup_$DATE.sql.gz" s3://musoftware-db-backups/
```
Ensure execution permissions: `sudo chmod +x /usr/local/bin/backup_erp.sh`.
Add to root crontab (`sudo crontab -e`):
```text
0 3 * * * /usr/local/bin/backup_erp.sh >> /var/log/backup_erp.log 2>&1
```

---

## 2. Log Rotation (`logrotate`)

Laravel application logs (`storage/logs/laravel.log`) and supervisor worker logs grow rapidly over time. Configure `logrotate` by creating `/etc/logrotate.d/erp-system`:

```text
/var/www/erp-system/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 664 www-data www-data
}
```

---

## 3. Clearing Application & Opcode Caches

When deploying minor patches or hotfixes, run the following cache clearing sequence:

```bash
cd /var/www/erp-system

# Flush application caching
php artisan cache:clear

# Flush expired user sessions from database/redis
php artisan auth:clear-resets

# Restart supervisor queue workers gracefully to load new PHP code in memory
php artisan queue:restart
```

---

## 4. Updating Dependencies & Security Patches

When applying upstream framework security patches or updating Node packages:

```bash
# Put platform in maintenance mode (Displays professional 503 screen to clients)
php artisan down --secret="secret-bypass-token-99"

# Update composer packages safely
composer update --optimize-autoloader --no-dev

# Recompile frontend UI assets
npm update
npm run build

# Re-warm system caches
php artisan optimize

# Bring platform back online
php artisan up
```
*Note on Maintenance Bypass:* You can still test the live site during maintenance mode by visiting `https://erp.musoftwares.com/secret-bypass-token-99`.
