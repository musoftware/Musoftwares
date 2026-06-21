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
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('web', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('tenant', function (Request $request) {
            return Limit::perMinute(1000)->by(session('tenant_id', $request->user()?->id ?: $request->ip()));
        });

        RateLimiter::for('webhooks', function (Request $request) {
            return Limit::perMinute(300)->by($request->ip());
        });
    }
}
