<?php

namespace App\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Traits\PathNamespace;

abstract class BaseModuleServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = '';

    protected string $nameLower = '';

    protected array $providers = [];

    protected array $commands = [];

    public function boot(): void
    {
        if ($this->name && $this->nameLower && function_exists('module_path')) {
            $viewsPath = module_path($this->name, 'resources/views');
            if (is_dir($viewsPath)) {
                $this->loadViewsFrom($viewsPath, $this->nameLower);

                $componentNamespace = $this->module_namespace($this->name, $this->app_path(config('modules.paths.generator.component-class.path', 'View/Components')));
                Blade::componentNamespace($componentNamespace, $this->nameLower);
            }
        }

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
