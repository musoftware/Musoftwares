<?php

namespace Modules\CRM\Providers;

use Illuminate\Support\ServiceProvider;

class CRMServiceProvider extends ServiceProvider
{
    /**
     * Register any module services.
     */
    public function register(): void
    {
        $this->app->singleton(\Modules\CRM\Infrastructure\Context\TenantContext::class, function () {
            return new \Modules\CRM\Infrastructure\Context\TenantContext();
        });
        
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Bootstrap any module services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(
            module_path('CRM', 'Database/Migrations')
        );
    }
}
