<?php

namespace App\Modules\BookingRules\Services\Processors\Types;

use App\Modules\BookingRules\Models\BookingAdvancedRuleAction;
use App\Modules\BookingRules\Models\BookingAdvancedRuleExecution;
use App\Modules\BookingRules\Services\Processors\RuleActionProcessorInterface;
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
