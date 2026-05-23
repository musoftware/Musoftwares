<?php

namespace Modules\Freelance\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Freelance\Console\ExpireOldJobs;

class FreelanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(
            module_path('Freelance', 'Database/Migrations')
        );

        if ($this->app->runningInConsole()) {
            $this->commands([
                ExpireOldJobs::class,
            ]);
        }
    }
}
