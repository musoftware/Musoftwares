<?php

namespace Modules\fbmb\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\fbmb\Services\FbmbLookupService;
use Modules\fbmb\Console\CleanupExpiredFbmbResults;

class FbmbServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(FbmbLookupService::class, function ($app) {
            return new FbmbLookupService($app->make('\App\Services\PointsService'));
        });
    }

    public function boot()
    {
        $this->loadMigrationsFrom(module_path('fbmb', 'Database/Migrations'));

        if ($this->app->runningInConsole()) {
            $this->commands([
                CleanupExpiredFbmbResults::class,
            ]);
        }

        Route::middleware('web')
            ->group(module_path('fbmb', 'routes/web.php'));
    }
}
