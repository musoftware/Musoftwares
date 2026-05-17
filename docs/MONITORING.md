# Monitoring, Observability & Error Tracking

## 1. Exception & Error Tracking (Sentry / Bugsnag)

To capture uncaught runtime exceptions, database deadlocks, or failed job executions across all modules in real time, the platform integrates with Bugsnag or Sentry.

### Sentry Integration (`config/sentry.php`)
```php
return [
    'dsn' => env('SENTRY_LARAVEL_DSN', env('SENTRY_DSN')),
    'environment' => env('APP_ENV'),
    'traces_sample_rate' => (float) env('SENTRY_TRACES_SAMPLE_RATE', 0.2), // Sample 20% of requests for performance profiling
    'send_default_pii' => false, // Ensure client PII is scrubbed before transmission
];
```

Inside `bootstrap/app.php`:
```php
->withExceptions(function (Exceptions $exceptions): void {
    $exceptions->reportable(function (Throwable $e) {
        if (app()->bound('sentry')) {
            app('sentry')->captureException($e);
        }
    });
})
```

---

## 2. Queue & Background Worker Monitoring (Laravel Horizon)

Because billing schedules, currency fetching, and email dispatching are processed asynchronously on Redis queues, **Laravel Horizon** is used for real-time queue observability.

### Horizon Access Rules
Horizon dashboard is available at `https://erp.musoftwares.com/horizon`. Access is strictly restricted by `Horizon::auth` in `app/Providers/HorizonServiceProvider.php`:

```php
protected function gate(): void
{
    Gate::define('viewHorizon', function ($user) {
        return in_array($user->email, [
            'admin@musoftwares.com',
            'sysadmin@musoftwares.com',
        ]);
    });
}
```

---

## 3. Server Health & Performance Metrics

### Nginx Stub Status Monitoring
To monitor active client connections and request throughput, enable Nginx stub status in your server configuration:

```nginx
location /nginx_status {
    stub_status on;
    access_log off;
    allow 127.0.0.1;
    allow 10.0.0.0/8; # Internal VPC monitoring subnets
    deny all;
}
```

### Redis Memory & Key Tracking
To ensure session and cache storage does not exhaust system RAM, configure memory limits in `/etc/redis/redis.conf`:

```ini
maxmemory 2gb
maxmemory-policy allkeys-lru
```

To monitor real-time Redis operations from the server terminal:
```bash
redis-cli monitor
```

---

## 4. Uptime & Synthetic Ping Monitoring

Configure an external monitoring service (e.g., UptimeRobot, Datadog Synthetic Ping, or Pingdom) to hit the dedicated health check endpoint every 60 seconds:

`GET https://erp.musoftwares.com/up`

If the database connection fails or Redis is unreachable, this endpoint returns HTTP `500` or `503`, triggering automated pager alerts to engineering staff.
