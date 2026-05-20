# Local Development Playbook

Follow these steps to safely run the MuSoftware ecosystem on a local Windows/Mac environment.

## 1. Prerequisites
- **PHP:** 8.3+ (Critical: Use exactly PHP 8.3 to avoid 8.1 compatibility bugs).
- **Node.js:** v18+
- **Database:** MySQL 8+ or SQLite (default for testing).
- **Composer & NPM**

## 2. Setting Up
```bash
cp .env.example .env
composer install
npm install
php artisan key:generate
php artisan migrate --seed
```

## 3. Local Queue & Caching
In local development, avoid the overhead of a Redis instance unless explicitly testing Redis features.
Ensure `.env` contains:
```env
CACHE_STORE=database
QUEUE_CONNECTION=database
SESSION_DRIVER=database
```

## 4. Running the Servers
You need three terminal tabs running concurrently:

**Tab 1: Web Server (Laravel)**
```bash
php artisan serve
```

**Tab 2: WebSockets (Reverb)**
```bash
php artisan reverb:start
```

**Tab 3: Frontend Compiler (Vite)**
```bash
npm run dev
```

## 5. Running Background Schedulers
To test scheduled commands (like subscriptions or recurring entries), you can manually invoke them instead of running a full cron daemon:
```bash
# Process recurring entries (dry-run first to verify)
php artisan erp:recurring:process --dry-run
php artisan erp:recurring:process

# Process subscription renewals
php artisan subscription:renew
```

## 6. Testing
Always run the full test suite before committing:
```bash
php artisan test
```
