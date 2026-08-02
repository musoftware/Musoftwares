<?php

namespace App\Console\Commands;

use App\Models\AdminSettings;
use App\Models\Project;
use App\Models\Task;
use App\Models\Todo;
use App\Models\User;
use App\Notifications\AdminDeveloperTasksNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendDailyAdminTasksNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:daily-tasks-notification {--force : Bypass timezone and work hours check}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily developer tasks notifications to admins during working hours';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $force = $this->option('force');

        // Force Cairo timezone
        $now = Carbon::now('Africa/Cairo');
        $dayOfWeek = $now->dayOfWeek; // 0 = Sunday, 1 = Monday, ..., 6 = Saturday

        $workDays = array_map('intval', explode(',', AdminSettings::GetValue('admin_work_days', '0,1,2,3,4')));
        $startTime = AdminSettings::GetValue('admin_work_start_time', '09:00');
        $endTime = AdminSettings::GetValue('admin_work_end_time', '17:00');

        if (!$force) {
            // Check if today is a working day
            if (!in_array($dayOfWeek, $workDays)) {
                $this->info("Today is a day off. Notification skipped.");
                return 0;
            }

            // Check if current time is within working hours
            $currentTimeString = $now->format('H:i');
            if ($currentTimeString < $startTime || $currentTimeString > $endTime) {
                $this->info("Outside working hours ({$currentTimeString} vs {$startTime}-{$endTime}). Notification skipped.");
                return 0;
            }
        }

        // Retrieve active AI projects
        $projects = Project::where('ai_enabled', true)
            ->where('archived', false)
            ->where('status', '!=', 'closed')
            ->get();

        if ($projects->isEmpty()) {
            $this->info("No active AI-enabled projects found.");
            return 0;
        }

        $summary = "";

        foreach ($projects as $project) {
            $openTasks = Task::where('project_id', $project->id)
                ->where('archived', false)
                ->get(['id', 'task_name', 'priority']);

            $openTodos = Todo::where('project_id', $project->id)
                ->where('completed', false)
                ->get(['id', 'title']);

            if ($openTasks->isNotEmpty() || $openTodos->isNotEmpty()) {
                $summary .= "\n📂 Project: {$project->project_name}\n";

                if ($openTasks->isNotEmpty()) {
                    $summary .= "  Tasks:\n";
                    foreach ($openTasks as $t) {
                        $summary .= "  - {$t->task_name} [Priority: {$t->priority}]\n";
                    }
                }

                if ($openTodos->isNotEmpty()) {
                    $summary .= "  Todos:\n";
                    foreach ($openTodos as $td) {
                        $summary .= "  - {$td->title}\n";
                    }
                }
            }
        }

        if (empty(trim($summary))) {
            $this->info("No open developer tasks or todos found for today.");
            return 0;
        }

        $title = "Daily Developer Tasks - " . $now->format('Y-m-d');
        $messageContent = trim($summary);

        // Fetch all Admins
        $admins = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['super_admin', 'admin', 'superadmin', 'Admin']);
        })->get();

        if ($admins->isEmpty()) {
            $this->warn("No admin users found to receive notification.");
            return 0;
        }

        foreach ($admins as $admin) {
            $admin->notify(new AdminDeveloperTasksNotification($title, $messageContent));
        }

        $this->info("Daily tasks notifications sent successfully to " . $admins->count() . " admin(s).");
        return 0;
    }
}
