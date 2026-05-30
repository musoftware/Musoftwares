<?php

namespace Modules\Booking\app\Features\BookingRules\Services\Processors\Types;

use Modules\Booking\app\Features\BookingRules\Models\BookingAdvancedRuleAction;
use Modules\Booking\app\Features\BookingRules\Models\BookingAdvancedRuleExecution;
use Modules\Booking\app\Features\BookingRules\Services\Processors\RuleActionProcessorInterface;

class RequireApprovalProcessor implements RuleActionProcessorInterface
{
    public function process(BookingAdvancedRuleAction $action, array $payload, BookingAdvancedRuleExecution $execution): void
    {
        $execution->update(['status' => 'pending_approval']);
        
        if (!$execution->is_dry_run && $execution->booking_id) {
            // e.g., Booking::find($execution->booking_id)->update(['status' => 'pending_approval']);
            
            // Notification or Event
            // event(new BookingRequiresApproval($execution->booking_id));
        }
    }
}
