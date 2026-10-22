<?php

namespace Modules\SoftwareStore\Providers;

use Illuminate\Support\ServiceProvider;

class SoftwareStoreServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(module_path('SoftwareStore', 'Database/Migrations'));
        $this->loadViewsFrom(module_path('SoftwareStore', 'Resources/views'), 'softwarestore');
        $this->loadRoutesFrom(module_path('SoftwareStore', 'routes/web.php'));
    }
}
