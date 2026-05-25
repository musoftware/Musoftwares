<?php

namespace Modules\Booking\app\Features\WaConfirm\Services;

use Modules\Booking\app\Features\WaConfirm\Models\BookingWaActionToken;
use Modules\Booking\app\Features\WaConfirm\Models\BookingWaLog;

class WhatsAppConfirmationActionProcessor
{
    /**
     * Executes the business logic corresponding to the customer's action token.
     */
    public function process(BookingWaActionToken $token)
    {
        $confirmation = $token->confirmation;
        $booking = $confirmation->booking;

        // Execute logic based on the action type
        switch ($token->action_type) {
            case 'confirm':
                $booking->status = 'confirmed';
                $booking->save();
                
                $confirmation->status = 'read';
                $confirmation->responded_at = now();
                $confirmation->save();
                
                event(new \Modules\Booking\app\Features\WaConfirm\Events\BookingConfirmedByCustomer($booking));
                break;
                
            case 'cancel':
                $booking->status = 'cancelled';
                $booking->save();
                
                $confirmation->status = 'read';
                $confirmation->responded_at = now();
                $confirmation->save();
                
                event(new \Modules\Booking\app\Features\WaConfirm\Events\BookingCancelledByCustomer($booking));
                break;

            case 'reschedule':
                // In a real app, this might just tag the booking and notify staff, 
                // or open a web-view to pick a new date. We will mark the status.
                $booking->status = 'reschedule_requested';
                $booking->save();
                
                $confirmation->status = 'read';
                $confirmation->responded_at = now();
                $confirmation->save();

                event(new \Modules\Booking\app\Features\WaConfirm\Events\BookingRescheduleRequested($booking));
                break;
        }

        // Log the action for audit trail
        BookingWaLog::create([
            'tenant_id' => $confirmation->tenant_id,
            'confirmation_id' => $confirmation->id,
            'event_type' => "customer_clicked_{$token->action_type}",
            'payload' => ['ip' => request()->ip()],
        ]);

        return true;
    }
}
