<?php

namespace Modules\Tools\Providers;

use Illuminate\Support\ServiceProvider;

class ToolsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(
            module_path('Tools', 'Database/Migrations')
        );

        $this->loadViewsFrom(module_path('Tools', 'resources/views'), 'tools');
    }
}
