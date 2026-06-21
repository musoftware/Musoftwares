<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BackgroundTask;
use App\Models\User;
use App\Events\BackgroundTaskUpdated;

class TestWebSocketStatus extends Command
{
    protected $signature = 'test:websocket {user_id=1}';
    protected $description = 'Test WebSocket Background Task Status';

    public function handle()
    {
        $userId = $this->argument('user_id');
        $user = User::find($userId);
        if (!$user) {
            $user = User::create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
            ]);
            $userId = $user->id;
            $this->info("Created dummy user with id {$userId}");
        }

        $task = BackgroundTask::create([
            'user_id' => $userId,
            'type' => 'test_task',
            'status' => 'pending',
            'progress' => 0,
            'payload' => [],
        ]);

        broadcast(new BackgroundTaskUpdated($task));
        $this->info("Task created and broadcasted");

        sleep(2);

        $task->update(['status' => 'processing', 'progress' => 50]);
        broadcast(new BackgroundTaskUpdated($task));
        $this->info("Task progress 50%");

        sleep(2);

        $task->update(['status' => 'completed', 'progress' => 100]);
        broadcast(new BackgroundTaskUpdated($task));
        $this->info("Task completed");
    }
}
