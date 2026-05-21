<?php

namespace Modules\Tools\Providers;

use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\ServiceProvider;
use Modules\Tools\Models\ResellerUserSession;
use Modules\Tools\Models\ToolSubscription;
use Modules\Tools\Observers\ResellerSubscriptionObserver;

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

        // ── Observers ────────────────────────────────────────────────────────
        // Auto-deduct reseller balance when a sub-user subscribes to a tool.
        ToolSubscription::observe(ResellerSubscriptionObserver::class);

        // ── Scheduled Tasks ──────────────────────────────────────────────────
        // Prune stale session heartbeats older than 30 minutes.
        // This keeps the reseller_user_sessions table lean and fast.
        Schedule::call(fn () => ResellerUserSession::pruneStale(30))
            ->everyFifteenMinutes()
            ->name('prune-reseller-sessions')
            ->withoutOverlapping();
    }
}
