<?php

namespace Modules\Booking\app\Features\MultiBranch\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Modules\Booking\app\Features\MultiBranch\Models\BookingBranch;

class YouWereAssignedToBranch extends Notification implements ShouldQueue
{
    use Queueable;

    public $branch;
    public $role;

    public function __construct(BookingBranch $branch, string $role)
    {
        $this->branch = $branch;
        $this->role = $role;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toArray($notifiable)
    {
        return [
            'message' => "You have been assigned to the branch: {$this->branch->name} as {$this->role}.",
            'branch_id' => $this->branch->id,
            'role' => $this->role,
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'message' => "You have been assigned to the branch: {$this->branch->name}.",
            'branch_id' => $this->branch->id,
        ]);
    }
}
