<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\LaravelModulesServiceProvider;
use App\Providers\EventServiceProvider;
use Modules\CRM\Infrastructure\Capabilities\CapabilityRegistry;
use Modules\CRM\Infrastructure\Capabilities\EntitlementEngine;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->register(LaravelModulesServiceProvider::class);
        $this->app->register(EventServiceProvider::class);

        // Bind the CapabilityRegistry as a singleton so it holds the DAG graph globally
        $this->app->singleton(CapabilityRegistry::class, function ($app) {
            $registry = new CapabilityRegistry();
            $addons = config('saas.addons', []);
            
            foreach ($addons as $key => $addonConfig) {
                $dependencies = [];
                if (!empty($addonConfig['parent'])) {
                    $dependencies[] = $addonConfig['parent'];
                }
                $registry->register($key, $dependencies, $addonConfig['desc'] ?? '');
            }
            
            return $registry;
        });

        // Bind the EntitlementEngine as a singleton
        $this->app->singleton(EntitlementEngine::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        $this->configureRateLimiting();

        if (class_exists(\Modules\CRM\Models\Lead::class) && class_exists(\Modules\CRM\Observers\LeadObserver::class)) {
            \Modules\CRM\Models\Lead::observe(\Modules\CRM\Observers\LeadObserver::class);
        }

        if (class_exists(\Modules\CRM\Models\LeadNote::class) && class_exists(\Modules\CRM\Observers\LeadNoteObserver::class)) {
            \Modules\CRM\Models\LeadNote::observe(\Modules\CRM\Observers\LeadNoteObserver::class);
        }

        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Auth\Events\Lockout::class,
            function (\Illuminate\Auth\Events\Lockout $event) {
                $ip = $event->request->ip();
                \App\Models\BlockedIp::firstOrCreate(
                    ['ip_address' => $ip],
                    ['reason' => 'Brute-force login attempt', 'blocked_until' => now()->addHours(24)]
                );
                \Illuminate\Support\Facades\Cache::forget("blocked_ip:{$ip}");
            }
        );
    }

    protected function configureRateLimiting(): void
    {
        $getLimit = function (string $module, int $defaultRequests, int $defaultDecay, Request $request) {
            $tenantId = session('tenant_id');
            $ip = $request->ip();

            $cacheKey = "rate_limit:{$module}:{$tenantId}:{$ip}";
            
            $config = \Illuminate\Support\Facades\Cache::remember($cacheKey, 60, function () use ($module, $tenantId, $ip) {
                if ($tenantId) {
                    $limit = \App\Models\RateLimit::where('module', $module)
                        ->where('tenant_id', $tenantId)
                        ->where('is_active', true)
                        ->first();
                    if ($limit) return $limit;
                }
                
                $limit = \App\Models\RateLimit::where('module', $module)
                    ->where('ip_address', $ip)
                    ->where('is_active', true)
                    ->first();
                if ($limit) return $limit;

                return \App\Models\RateLimit::where('module', $module)
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
            return $getLimit('api', 60, 1, $request)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('web', function (Request $request) use ($getLimit) {
            return $getLimit('web', 120, 1, $request)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('tenant', function (Request $request) use ($getLimit) {
            return $getLimit('tenant', 1000, 1, $request)->by(session('tenant_id', $request->user()?->id ?: $request->ip()));
        });

        RateLimiter::for('webhooks', function (Request $request) use ($getLimit) {
            return $getLimit('webhooks', 300, 1, $request)->by($request->ip());
        });
    }
}
