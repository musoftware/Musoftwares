<?php

namespace Modules\AppBuilder\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\AppBuilder\Services\Chat\ChatModel;
use Modules\AppBuilder\Services\Chat\ClaudeChatModel;

class AppBuilderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(module_path('AppBuilder', 'config/builder.php'), 'builder');
        $this->app->bind(ChatModel::class, ClaudeChatModel::class);
        $this->app->bind(\Modules\AppBuilder\Services\Study\StudyWriter::class, \Modules\AppBuilder\Services\Study\ClaudeStudyWriter::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(module_path('AppBuilder', 'Database/Migrations'));
        $this->loadViewsFrom(module_path('AppBuilder', 'Resources/views'), 'appbuilder');
        $this->loadRoutesFrom(module_path('AppBuilder', 'routes/web.php'));
    }
}
