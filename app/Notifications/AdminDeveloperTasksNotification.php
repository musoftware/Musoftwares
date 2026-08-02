<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AdminDeveloperTasksNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public string $title;
    public string $messageContent;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $title, string $messageContent)
    {
        $this->title = $title;
        $this->messageContent = $messageContent;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification for database storage.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->messageContent,
            'string_data' => $this->title . ': ' . $this->messageContent,
            'icon' => 'ListTodo',
        ];
    }
}
