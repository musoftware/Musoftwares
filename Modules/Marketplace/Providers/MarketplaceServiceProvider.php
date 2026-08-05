<?php

namespace Modules\Marketplace\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Modules\Marketplace\Models\ServiceOrder;
use Modules\Marketplace\Models\Service;
use Modules\Marketplace\Policies\ServiceOrderPolicy;
use Modules\Marketplace\Policies\ServicePolicy;

class MarketplaceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(
            module_path('Marketplace', 'Database/Migrations')
        );

        // Register Policies
        Gate::policy(ServiceOrder::class, ServiceOrderPolicy::class);
        Gate::policy(Service::class, ServicePolicy::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                \Modules\Marketplace\Console\GenerateAiFilesCommand::class,
                \Modules\Marketplace\Console\SendDailyDiscountNotificationsCommand::class,
            ]);
        }
    }
}

