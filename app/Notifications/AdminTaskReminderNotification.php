<?php

namespace App\Notifications;

use App\Models\Task;

use App\Notifications\Traits\BuildsFcmMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminTaskReminderNotification extends Notification implements ShouldQueue
{
    use BuildsFcmMessage, Queueable;

    public Task $task;

    /**
     * Create a new notification instance.
     */
    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'fcm'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $projectName = $this->task->project?->project_name ?? 'المشروع';
        $taskName    = $this->task->task_name ?? 'مهمة برمجية';
        $startTime   = $this->task->start_at ? \Carbon\Carbon::parse($this->task->start_at)->format('Y-m-d H:i') : 'الآن';

        return (new MailMessage)
            ->subject('⏰ تذكير بموعد تنفيذ مهمة: ' . $taskName)
            ->greeting('أهلاً بك يا هندسة 👋')
            ->line("تذكير: لديك مهمة برمجية تبدأ خلال 15 دقيقة.")
            ->line("المشروع: {$projectName}")
            ->line("المهمة: {$taskName}")
            ->line("موعد البداية المتوقع: {$startTime}")
            ->action('عرض تفاصيل المهمة والمشروع', url('/projects/' . $this->task->project_id));
    }

    /**
     * Get the FCM representation of the notification.
     */
    public function toFcm(object $notifiable)
    {
        $taskName = $this->task->task_name ?? 'مهمة جديدة';
        $projectName = $this->task->project?->project_name ?? '';

        return [
            'title' => '⏰ تذكير بموعد المهمة (' . $projectName . ')',
            'body'  => "المهمة '{$taskName}' تبدأ خلال 15 دقيقة. يرجى الاستعداد للتنفيذ.",
            'data'  => [
                'type'       => 'task_reminder',
                'task_id'    => (string) $this->task->id,
                'project_id' => (string) $this->task->project_id,
            ],
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title'      => '⏰ تذكير بموعد المهمة: ' . $this->task->task_name,
            'message'    => 'تبدأ المهمة خلال 15 دقيقة في مشروع ' . ($this->task->project?->project_name ?? ''),
            'task_id'    => $this->task->id,
            'project_id' => $this->task->project_id,
            'start_at'   => $this->task->start_at,
        ];
    }
}
