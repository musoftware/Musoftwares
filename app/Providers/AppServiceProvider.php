<?php

namespace App\Providers;

use App\Models\BlockedIp;
use App\Models\RateLimit;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\LaravelModulesServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->register(LaravelModulesServiceProvider::class);
        $this->app->register(EventServiceProvider::class);
        $this->app->register(NotificationServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Disabled Vite global prefetch to prevent massive HTML inline asset manifest bloat
        // Vite::prefetch(concurrency: 3);

        \Illuminate\Database\Eloquent\Relations\Relation::morphMap([
            'App\Models\ServiceOrder' => \Modules\Marketplace\Models\ServiceOrder::class,
            'service_order' => \Modules\Marketplace\Models\ServiceOrder::class,
            'marketplace_order' => \Modules\Marketplace\Models\ServiceOrder::class,
            \Modules\Marketplace\Models\ServiceOrder::class => \Modules\Marketplace\Models\ServiceOrder::class,
            'App\Models\Ticket' => \App\Models\Ticket::class,
            'support_ticket' => \App\Models\Ticket::class,
            'ticket' => \App\Models\Ticket::class,
            'App\Models\User' => \App\Models\User::class,
            'direct_message' => \App\Models\User::class,
        ]);


        $this->configureRateLimiting();

        Event::listen(
            Lockout::class,
            function (Lockout $event) {
                $ip = $event->request->ip();
                BlockedIp::firstOrCreate(
                    ['ip_address' => $ip],
                    ['reason' => 'Brute-force login attempt', 'blocked_until' => now()->addHours(24)]
                );
                Cache::forget("blocked_ip:{$ip}");
            }
        );
    }

    protected function configureRateLimiting(): void
    {
        $getLimit = function (string $module, int $defaultRequests, int $defaultDecay, Request $request) {
            $tenantId = session('tenant_id');
            $ip = $request->ip();

            $cacheKey = "rate_limit:{$module}:{$tenantId}:{$ip}";

            $config = Cache::remember($cacheKey, 60, function () use ($module, $tenantId, $ip) {
                if ($tenantId) {
                    $limit = RateLimit::where('module', $module)
                        ->where('tenant_id', $tenantId)
                        ->where('is_active', true)
                        ->first();
                    if ($limit) {
                        return $limit;
                    }
                }

                $limit = RateLimit::where('module', $module)
                    ->where('ip_address', $ip)
                    ->where('is_active', true)
                    ->first();
                if ($limit) {
                    return $limit;
                }

                return RateLimit::where('module', $module)
                    ->whereNull('tenant_id')
                    ->whereNull('ip_address')
                    ->where('is_active', true)
                    ->first();
            });

            $maxRequests = $config ? $config->max_requests : $defaultRequests;
            $decayMinutes = $config ? $config->decay_minutes : $defaultDecay;

            return Limit::perMinute($maxRequests, $decayMinutes);
        };

        RateLimiter::for('api', function (Request $request) use ($getLimit) {
            return $getLimit('api', 300, 1, $request)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('web', function (Request $request) use ($getLimit) {
            return $getLimit('web', 600, 1, $request)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('tenant', function (Request $request) use ($getLimit) {
            return $getLimit('tenant', 5000, 1, $request)->by(session('tenant_id', $request->user()?->id ?: $request->ip()));
        });

        RateLimiter::for('webhooks', function (Request $request) use ($getLimit) {
            return $getLimit('webhooks', 1500, 1, $request)->by($request->ip());
        });
    }
}
