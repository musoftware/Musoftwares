<?php

namespace App\Console\Commands;

use App\Jobs\DemoLongRunningTask;
use App\Models\User;
use App\Services\BackgroundTaskService;
use Illuminate\Console\Command;

class DispatchDemoTask extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:demo {user_id=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch a demo background task for testing';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('user_id');
        $user = User::find($userId);

        if (! $user) {
            $this->error("User $userId not found.");

            return;
        }

        $task = BackgroundTaskService::dispatch($user->id, 'demo_task', DemoLongRunningTask::class, [
            'steps' => 10,
        ]);

        $this->info("Dispatched demo task #{$task->id} for user {$user->name}");
    }
}
