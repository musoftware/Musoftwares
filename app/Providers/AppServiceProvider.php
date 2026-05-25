<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\LaravelModulesServiceProvider;
use App\Providers\EventServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->register(LaravelModulesServiceProvider::class);
        $this->app->register(EventServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        if (class_exists(\Modules\CRM\Models\Lead::class) && class_exists(\Modules\CRM\Observers\LeadObserver::class)) {
            \Modules\CRM\Models\Lead::observe(\Modules\CRM\Observers\LeadObserver::class);
        }

        if (class_exists(\Modules\CRM\Models\LeadNote::class) && class_exists(\Modules\CRM\Observers\LeadNoteObserver::class)) {
            \Modules\CRM\Models\LeadNote::observe(\Modules\CRM\Observers\LeadNoteObserver::class);
        }
    }
}
