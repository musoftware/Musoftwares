<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;

abstract class BaseModuleServiceProvider extends ServiceProvider
{
    protected string $name = '';
    protected string $nameLower = '';
    protected array $providers = [];
    protected array $commands = [];

    public function boot(): void
    {
        if (method_exists($this, 'configureSchedules')) {
            $this->app->booted(function () {
                $schedule = $this->app->make(Schedule::class);
                $this->configureSchedules($schedule);
            });
        }
    }

    public function register(): void
    {
        foreach ($this->providers as $provider) {
            $this->app->register($provider);
        }
    }
}
