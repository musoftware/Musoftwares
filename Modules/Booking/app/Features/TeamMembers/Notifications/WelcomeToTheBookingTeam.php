<?php

namespace Modules\Booking\app\Features\TeamMembers\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Modules\Booking\app\Features\TeamMembers\Models\BookingTeamMember;

class WelcomeToTheBookingTeam extends Notification implements ShouldQueue
{
    use Queueable;

    public $teamMember;

    public function __construct(BookingTeamMember $teamMember)
    {
        $this->teamMember = $teamMember;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast']; // Add 'mail' when email integration is ready
    }

    public function toArray($notifiable)
    {
        return [
            'message' => "Welcome to the booking team! Your profile as {$this->teamMember->job_title} is now active.",
            'team_member_id' => $this->teamMember->id,
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'message' => "Welcome to the booking team! Your profile is now active.",
            'team_member_id' => $this->teamMember->id,
        ]);
    }
}
