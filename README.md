# Musoftwares

The Musoftwares business platform: client portal, admin panel, invoicing, wallets, loyalty, projects,
CRM/ERP tools and a set of product modules. Built with Laravel 12, Inertia and React.

It runs on simple hosting only: Apache, PHP, MySQL and cron. No Redis, no Supervisor, no Node process
in production. See `.agents/rules/simple-hosting-stack-apache-mysql-cronjobs-only.md`.

## Requirements

- PHP 8.4 or newer (the lock file needs 8.4.1+). Locally we use `C:\tools\php83\php.exe`.
- MySQL 8 (or MariaDB) for local and production data. Tests use in-memory SQLite.
- Composer 2 (`composer.phar` is git-ignored; download your own copy if needed).
- Node 22 and npm. Only needed to build the frontend. Production never runs Node.

## Local setup

```bash
composer install
npm ci

# Local settings live in .env.local (git-ignored). Start from the example:
cp .env.example .env.local
# Edit .env.local: set DB_CONNECTION=mysql and your local DB name, user and password.
# Use CACHE_STORE=database, SESSION_DRIVER=database and QUEUE_CONNECTION=database.

C:\tools\php83\php.exe artisan key:generate --env=local
C:\tools\php83\php.exe artisan migrate --env=local
npm run build
```

Start the app. Always pass `--env=local`, or Laravel reads `.env` (production settings):

```bash
C:\tools\php83\php.exe artisan serve --host=127.0.0.1 --port=8000 --env=local
```

`serve.bat` does the same. `start-local-stack.bat` also starts the GoldSaverSys app next to it.
Use `npm run dev` for hot reload while working on the frontend.

## Modules

Feature modules live in `Modules/` (nwidart/laravel-modules):

| Module | Purpose |
| --- | --- |
| DigitalProducts | Digital products and ebook library |
| Fbmb | Facebook ID to mobile lookup |
| Listing | Property listings and scraper |
| Marketplace | Marketplace for services and products |
| PasswordSync | Encrypted password vault sync API |
| PaymentGateway | Kashier payment gateway service for external clients |
| Series | Video series with progress and notes |
| Shared | Shared code used by other modules |
| Shortlink | Internal URL shortener |
| SmsPaymentGateway | SMS based payment matching (Android app, hosted checkout, API) |
| WhatsappSender | WhatsApp Cloud API sender |
| WrittenCoursesEngine | Written courses |

## Tests

PHP tests (Pest). Copy the test env once, then run in parallel:

```bash
cp .env.testing.example .env.testing
C:\tools\php83\php.exe artisan key:generate --env=testing
C:\tools\php83\php.exe vendor/bin/pest --parallel
```

Frontend unit tests (Vitest) and browser tests (Playwright, against a running local server):

```bash
npm test
npx playwright install chromium   # first time only
npm run test:e2e
```

Other checks:

```bash
npx tsc --noEmit
C:\tools\php83\php.exe vendor/bin/phpstan analyse --memory-limit=2G
C:\tools\php83\php.exe vendor/bin/pint --test path/to/changed/File.php
```

Pint uses the Laravel preset (`pint.json`). Only format the files you change.
Do not reformat the whole repo in one go.

CI (`.github/workflows/e2e-testing.yml`) runs Pint on changed files, PHPStan, Pest, and the
TypeScript check plus build.

## Scheduler and queue (cron)

All background work runs from cron. Add these lines on the server:

```cron
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
* * * * * cd /path/to/app && php artisan queue:work --stop-when-empty --max-time=55 >> /dev/null 2>&1
```

Scheduled tasks are defined in `routes/console.php`.

## Deploy

Copy `deploy/.ssh-config.example` to `deploy/.ssh-config` and fill it in. Prefer `SSH_KEY`
(a key file) over `SSH_PASSWORD`. Connect to the server once by hand to accept its host key.

Fast deploy:

```bash
deploy-fast.bat              # same as: powershell -File deploy\fast.ps1
deploy-fast.bat -DryRun      # list the files that would be uploaded
deploy-fast.bat -Build       # run npm run build first
```

`deploy\fast.ps1` is gated:

1. It refuses to deploy uncommitted changes. Pass `-AllowDirty` to override.
2. It runs the Pest suite locally and stops if anything fails. `-SkipTests` skips it only after
   you type `SKIP-TESTS`.
3. After upload it shows pending migrations on the server and runs `migrate --force` only after
   you type `MIGRATE`.

Other scripts in `deploy/` cover single tasks (push PHP files, push vendor, migrate, clear cache).

## Rules for contributors

Engineering rules live in `AGENTS.md` and `.agents/rules/`. Read them before changing code.
Secrets never go in git: `.env*` files (except the `*.example` ones) and `deploy/.ssh-config`
are ignored.
