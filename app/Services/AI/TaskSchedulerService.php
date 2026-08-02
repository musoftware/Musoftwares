<?php

namespace App\Services\AI;

use App\Models\AdminSettings;
use App\Models\Project;
use App\Models\Task;
use App\Models\Todo;
use App\Models\User;
use App\Notifications\AdminTaskReminderNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TaskSchedulerService
{
    /**
     * Schedule all tasks & todos for a project based on Admin Working Hours
     * and queue 15-minute advance FCM + Email alerts for administrators.
     */
    public function scheduleProjectTasks(Project $project): array
    {
        // 1. Load Admin Working Hours Settings
        $rawWorkDays = AdminSettings::GetValue('work_days', '[0,1,2,3,4]'); // 0=Sunday, 4=Thursday
        $workDays    = json_decode($rawWorkDays, true) ?: [0, 1, 2, 3, 4];

        $workStartStr = AdminSettings::GetValue('work_start_time', '09:00');
        $workEndStr   = AdminSettings::GetValue('work_end_time', '17:00');
        $reminderMins = (int) AdminSettings::GetValue('admin_reminder_minutes', 15);

        // Parse start and end hours (e.g. 9 and 17)
        [$startHour, $startMinute] = explode(':', $workStartStr) + [9, 0];
        [$endHour, $endMinute]     = explode(':', $workEndStr) + [17, 0];

        $tz = 'Africa/Cairo';
        $now = Carbon::now($tz);

        // Find initial slot (next working day/hour)
        $cursor = $this->getNextAvailableWorkingSlot($now, $workDays, (int)$startHour, (int)$startMinute, (int)$endHour, (int)$endMinute);

        // Fetch project tasks and todos that need scheduling
        $tasks = Task::where('project_id', $project->id)->orderBy('id')->get();
        $todos = Todo::where('project_id', $project->id)->orderBy('id')->get();

        $scheduledCount = 0;

        // 2. Schedule Tasks (each task duration ~ 2 hours)
        foreach ($tasks as $task) {
            $taskStart = $cursor->copy();
            $taskEnd   = $taskStart->copy()->addHours(2);

            // If taskEnd exceeds workEnd, push to next working day
            if ($taskEnd->hour > $endHour || ($taskEnd->hour == $endHour && $taskEnd->minute > $endMinute)) {
                $cursor = $this->getNextAvailableWorkingSlot($cursor->copy()->addDay(), $workDays, (int)$startHour, (int)$startMinute, (int)$endHour, (int)$endMinute);
                $taskStart = $cursor->copy();
                $taskEnd   = $taskStart->copy()->addHours(2);
            }

            $task->update([
                'start_at' => $taskStart->format('Y-m-d H:i:s'),
                'due_date' => $taskEnd->format('Y-m-d H:i:s'),
            ]);

            // Advance cursor for next item
            $cursor = $taskEnd->copy();
            $scheduledCount++;

            // 3. Queue 15-minute advance FCM + Email Reminder for Admin
            $this->queueAdminReminder($task, $taskStart, $reminderMins);
        }

        // 4. Schedule Todos
        foreach ($todos as $todo) {
            $todoStart = $cursor->copy();
            $todoEnd   = $todoStart->copy()->addHour();

            if ($todoEnd->hour > $endHour || ($todoEnd->hour == $endHour && $todoEnd->minute > $endMinute)) {
                $cursor = $this->getNextAvailableWorkingSlot($cursor->copy()->addDay(), $workDays, (int)$startHour, (int)$startMinute, (int)$endHour, (int)$endMinute);
                $todoStart = $cursor->copy();
                $todoEnd   = $todoStart->copy()->addHour();
            }

            $todo->update([
                'start_at' => $todoStart->format('Y-m-d H:i:s'),
                'end_at'   => $todoEnd->format('Y-m-d H:i:s'),
                'inDate'   => $todoStart->format('Y-m-d'),
            ]);

            $cursor = $todoEnd->copy();
            $scheduledCount++;
        }

        return [
            'scheduled_items' => $scheduledCount,
            'first_start'     => $tasks->first()?->start_at,
            'last_due'        => $tasks->last()?->due_date,
        ];
    }

    /**
     * Dispatch delayed notification to Admins 15 minutes before task start.
     */
    protected function queueAdminReminder(Task $task, Carbon $taskStart, int $reminderMins): void
    {
        $notificationTime = $taskStart->copy()->subMinutes($reminderMins);

        // If notification time is in the past, dispatch immediately
        $delay = $notificationTime->isPast() ? 0 : $notificationTime->diffInSeconds(Carbon::now($taskStart->timezone));

        // Find Admin Users (e.g. role admin or super admin, or first admin user)
        $admins = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['admin', 'super-admin']);
        })->get();

        if ($admins->isEmpty()) {
            $admins = User::where('id', 1)->get();
        }

        foreach ($admins as $admin) {
            $admin->notify((new AdminTaskReminderNotification($task))->delay($delay));
        }
    }

    /**
     * Calculate next valid working slot given days and working hours.
     */
    protected function getNextAvailableWorkingSlot(Carbon $from, array $workDays, int $startHour, int $startMin, int $endHour, int $endMin): Carbon
    {
        $date = $from->copy();

        for ($i = 0; $i < 14; $i++) {
            $dayOfWeek = $date->dayOfWeek; // 0 (Sunday) to 6 (Saturday)

            if (in_array($dayOfWeek, $workDays)) {
                $dayStart = $date->copy()->setTime($startHour, $startMin, 0);
                $dayEnd   = $date->copy()->setTime($endHour, $endMin, 0);

                if ($date->lt($dayStart)) {
                    return $dayStart;
                }
                if ($date->lt($dayEnd)) {
                    return $date;
                }
            }

            // Move to next day 09:00 AM
            $date = $date->addDay()->setTime($startHour, $startMin, 0);
        }

        return $from;
    }
}
