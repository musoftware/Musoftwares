<?php

namespace Modules\fbmb\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\fbmb\Services\FbmbLookupService;

class FbmbServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(FbmbLookupService::class, function ($app) {
            return new FbmbLookupService($app->make('\Modules\Core\Services\WalletService'));
        });
    }

    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
    }
}
