<?php

namespace Modules\Core\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Console\FetchExchangeRates;

class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);

        // Register module services
        $this->app->singleton(
            \Modules\Core\Services\ExchangeRateService::class
        );
        $this->app->singleton(
            \Modules\Core\Services\WalletService::class
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(
            module_path('Core', 'Database/Migrations')
        );

        $this->loadTranslationsFrom(
            module_path('Core', 'Resources/lang'), 'core'
        );

        if ($this->app->runningInConsole()) {
            $this->commands([
                FetchExchangeRates::class,
            ]);
        }
    }
}