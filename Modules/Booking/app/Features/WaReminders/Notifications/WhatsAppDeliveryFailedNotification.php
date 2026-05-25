<?php

namespace Modules\Booking\app\Features\WaReminders\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Modules\Booking\app\Features\WaReminders\Models\WaLog;

class WhatsAppDeliveryFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $log;

    public function __construct(WaLog $log)
    {
        $this->log = $log;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toArray($notifiable)
    {
        return [
            'message' => "WhatsApp reminder failed to send to {$this->log->phone_number}. Reason: {$this->log->error_reason}",
            'log_id' => $this->log->id,
            'booking_id' => $this->log->booking_id,
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'message' => "WhatsApp reminder failed to send to {$this->log->phone_number}.",
            'log_id' => $this->log->id,
        ]);
    }
}
