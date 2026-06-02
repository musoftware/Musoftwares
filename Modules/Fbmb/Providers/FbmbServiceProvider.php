<?php

namespace Modules\Fbmb\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Fbmb\Services\FbmbLookupService;
use Modules\Fbmb\Console\CleanupExpiredFbmbResults;
use Modules\Fbmb\Console\ProcessPendingFbmbLookups;

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
        $this->loadMigrationsFrom(module_path('Fbmb', 'Database/Migrations'));

        if ($this->app->runningInConsole()) {
            $this->commands([
                CleanupExpiredFbmbResults::class,
                ProcessPendingFbmbLookups::class,
            ]);
        }

        Route::middleware('web')
            ->group(module_path('Fbmb', 'routes/web.php'));
    }
}
