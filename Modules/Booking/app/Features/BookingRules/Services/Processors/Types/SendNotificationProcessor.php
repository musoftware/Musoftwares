<?php

namespace Modules\Booking\app\Features\BookingRules\Services\Processors\Types;

use Modules\Booking\app\Features\BookingRules\Models\BookingAdvancedRuleAction;
use Modules\Booking\app\Features\BookingRules\Models\BookingAdvancedRuleExecution;
use Modules\Booking\app\Features\BookingRules\Services\Processors\RuleActionProcessorInterface;
use Illuminate\Support\Facades\Notification;

class SendNotificationProcessor implements RuleActionProcessorInterface
{
    public function process(BookingAdvancedRuleAction $action, array $payload, BookingAdvancedRuleExecution $execution): void
    {
        if ($execution->is_dry_run) {
            return;
        }

        // Example: Send notification to tenant admins or specific users
        // Notification::send($admins, new BookingRuleAlertNotification($action->parameters['message']));
        
        $execution->update(['status' => 'success']);
    }
}
