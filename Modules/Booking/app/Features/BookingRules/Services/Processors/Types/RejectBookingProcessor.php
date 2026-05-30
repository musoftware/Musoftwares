<?php

namespace Modules\Booking\app\Features\BookingRules\Services\Processors\Types;

use Modules\Booking\app\Features\BookingRules\Models\BookingAdvancedRuleAction;
use Modules\Booking\app\Features\BookingRules\Models\BookingAdvancedRuleExecution;
use Modules\Booking\app\Features\BookingRules\Services\Processors\RuleActionProcessorInterface;

class RejectBookingProcessor implements RuleActionProcessorInterface
{
    public function process(BookingAdvancedRuleAction $action, array $payload, BookingAdvancedRuleExecution $execution): void
    {
        // Mark execution status
        $execution->update(['status' => 'success']);
        
        // Example: dispatch an event that stops the booking or update booking directly
        if (!$execution->is_dry_run && $execution->booking_id) {
            // e.g., Booking::find($execution->booking_id)->update(['status' => 'rejected']);
            
            // Dispatch domain event
            event(new \Modules\Booking\app\Features\BookingRules\Events\BookingRuleBlockedBooking(
                $execution->tenant_id, 
                $execution->id, 
                $action->parameters['reason'] ?? 'Rejected by automated rule'
            ));
        }
    }
}
