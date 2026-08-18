<?php

namespace Modules\DigitalProducts\Providers;

use Illuminate\Support\ServiceProvider;

class DigitalProductsServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'DigitalProducts';
    protected string $moduleNameLower = 'digitalproducts';

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);

        $this->mergeConfigFrom(
            module_path($this->moduleName, 'Config/config.php'),
            $this->moduleNameLower
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(
            module_path($this->moduleName, 'Database/Migrations')
        );

        $this->loadViewsFrom(
            module_path($this->moduleName, 'Resources/views'),
            'digitalproducts'
        );
    }
}
