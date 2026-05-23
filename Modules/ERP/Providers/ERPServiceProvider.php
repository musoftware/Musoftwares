<?php

namespace Modules\ERP\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\ERP\Console\ProcessRecurringEntries;

class ERPServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(
            module_path('ERP', 'Database/Migrations')
        );

        $this->loadViewsFrom(module_path('ERP', 'resources/views'), 'erp');

        if ($this->app->runningInConsole()) {
            $this->commands([
                ProcessRecurringEntries::class,
            ]);
        }
    }
}
