<?php

namespace Modules\Booking\app\Features\WaReminders\Services;

use Modules\Booking\app\Features\WaReminders\Models\WaLog;
use Modules\Booking\app\Features\WaReminders\Events\BookingReminderDelivered;

class WhatsAppDeliveryTracker
{
    /**
     * Process a webhook payload to update message status.
     */
    public function updateDeliveryStatus(string $providerMessageId, string $status, ?string $errorReason = null)
    {
        $log = WaLog::where('provider_message_id', $providerMessageId)->first();
        
        if ($log) {
            $log->update([
                'delivery_status' => $status,
                'error_reason' => $errorReason,
            ]);

            if ($status === 'delivered' || $status === 'read') {
                event(new BookingReminderDelivered($log));
            }
            
            // if failed, we could fire BookingReminderFailed here too
        }
    }
}
