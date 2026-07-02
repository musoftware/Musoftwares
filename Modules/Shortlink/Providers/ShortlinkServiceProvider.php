<?php

namespace Modules\Shortlink\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Shortlink\Models\ShortlinkLink;
use Modules\Shortlink\Policies\ShortlinkLinkPolicy;

class ShortlinkServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(
            module_path('Shortlink', 'Database/Migrations')
        );

        // The model lives outside App\Models, so register the policy explicitly
        // (Laravel's auto-discovery only scans App\Models by default).
        Gate::policy(ShortlinkLink::class, ShortlinkLinkPolicy::class);
    }
}
