<?php

namespace Modules\Booking\app\Features\WaReminders\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;

class WhatsAppQuotaExceededNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $tenantId;

    public function __construct(int $tenantId)
    {
        $this->tenantId = $tenantId;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast']; // Add email for quota alerts
    }

    public function toArray($notifiable)
    {
        return [
            'message' => 'Your WhatsApp reminder limit has been reached. Please upgrade your plan to continue sending messages.',
            'action_url' => '/billing/upgrade',
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'message' => 'Your WhatsApp reminder limit has been reached.',
            'action_url' => '/billing/upgrade',
        ]);
    }
}
