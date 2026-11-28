<?php

namespace Modules\AppBuilder\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\AppBuilder\Services\Chat\ChatModel;
use Modules\AppBuilder\Services\Chat\ClaudeChatModel;
use Modules\AppBuilder\Services\ClaudeScopeCounter;
use Modules\AppBuilder\Services\ScopeCounter;
use Modules\AppBuilder\Services\Starters\ClaudeStarterCardWriter;
use Modules\AppBuilder\Services\Starters\StarterCardWriter;
use Modules\AppBuilder\Services\Study\ClaudeStudyWriter;
use Modules\AppBuilder\Services\Study\StudyWriter;

class AppBuilderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(module_path('AppBuilder', 'config/builder.php'), 'builder');
        $this->app->bind(ChatModel::class, ClaudeChatModel::class);
        $this->app->bind(StudyWriter::class, ClaudeStudyWriter::class);
        $this->app->bind(StarterCardWriter::class, ClaudeStarterCardWriter::class);
        $this->app->bind(ScopeCounter::class, ClaudeScopeCounter::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(module_path('AppBuilder', 'Database/Migrations'));
        $this->loadViewsFrom(module_path('AppBuilder', 'Resources/views'), 'appbuilder');
        $this->loadRoutesFrom(module_path('AppBuilder', 'routes/web.php'));
    }
}
