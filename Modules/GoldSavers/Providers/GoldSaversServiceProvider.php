<?php

namespace Modules\GoldSavers\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\GoldSavers\Console\Commands\FetchGoldPrices;
use Modules\GoldSavers\Console\Commands\FetchWorldGoldPrices;

class GoldSaversServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register bindings if any
    }

    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(module_path('GoldSavers', 'Database/Migrations'));

        // Load web routes
        if (file_exists(module_path('GoldSavers', 'routes/web.php'))) {
            Route::middleware('web')
                ->group(module_path('GoldSavers', 'routes/web.php'));
        }

        // Register Artisan commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                FetchGoldPrices::class,
                FetchWorldGoldPrices::class,
            ]);
        }
    }
}
