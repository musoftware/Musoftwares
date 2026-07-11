<?php

namespace App\Notifications;

use App\Models\EmployeeTodo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class EmployeeTodoAssigned extends Notification implements ShouldQueue
{
    use Queueable;

    public EmployeeTodo $todo;

    /**
     * Create a new notification instance.
     */
    public function __construct(EmployeeTodo $todo)
    {
        $this->todo = $todo;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'employee_todo_assigned',
            'title' => 'New Task Assigned',
            'message' => 'You have been assigned a new task: '.$this->todo->title,
            'todo_id' => $this->todo->id,
            'priority' => $this->todo->priority,
            'url' => '/dashboard', // Adjust later if there is a specific page for employees
        ];
    }
}
