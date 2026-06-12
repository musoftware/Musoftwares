<?php

namespace App\Jobs;

use App\Models\Todo;
use App\Models\User;
use App\Services\Integrations\GoogleCalendarService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncTodoToGoogleCalendar implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $todo;
    public $user; // The platform admin
    public $action; // 'create', 'update', 'delete'

    /**
     * Create a new job instance.
     */
    public function __construct(Todo $todo, User $user, string $action = 'create')
    {
        $this->todo = $todo;
        $this->user = $user;
        $this->action = $action;
    }

    /**
     * Execute the job.
     */
    public function handle(GoogleCalendarService $calendarService): void
    {
        if ($this->action === 'delete') {
            if ($this->todo->google_event_id) {
                $calendarService->deleteEvent($this->user, $this->todo->google_event_id);
            }
            return;
        }

        if (!$this->todo->start_at || !$this->todo->end_at) {
            return;
        }

        $start = Carbon::parse($this->todo->start_at);
        $end = Carbon::parse($this->todo->end_at);
        
        $title = "Task: " . $this->todo->title;
        $clientName = $this->todo->user ? $this->todo->user->name : 'Unknown';
        $description = "Client: " . $clientName . "\nDescription: " . ($this->todo->description ?? '');

        if ($this->action === 'create' || !$this->todo->google_event_id) {
            $eventId = $calendarService->createEvent($this->user, $title, $description, $start, $end);
            if ($eventId) {
                $this->todo->updateQuietly(['google_event_id' => $eventId]);
            }
        } elseif ($this->action === 'update' && $this->todo->google_event_id) {
            $calendarService->updateEvent($this->user, $this->todo->google_event_id, $title, $description, $start, $end);
        }
    }
}
