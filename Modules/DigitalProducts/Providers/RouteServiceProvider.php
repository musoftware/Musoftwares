<?php

namespace Modules\DigitalProducts\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    protected string $moduleNamespace = 'Modules\DigitalProducts\Http\Controllers';

    public function map(): void
    {
        $this->mapWebRoutes();
    }

    protected function mapWebRoutes(): void
    {
        Route::middleware('web')
            ->namespace($this->moduleNamespace)
            ->group(module_path('DigitalProducts', '/routes/web.php'));

        Route::middleware(['web', 'auth'])
            ->namespace($this->moduleNamespace)
            ->prefix('admin')
            ->group(module_path('DigitalProducts', '/routes/admin.php'));
    }
}
