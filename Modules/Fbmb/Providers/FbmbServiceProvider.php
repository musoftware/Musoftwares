<?php

namespace Modules\fbmb\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\fbmb\Services\FbmbLookupService;

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
        Route::middleware('web')
            ->group(module_path('fbmb', 'routes/web.php'));
    }
}
