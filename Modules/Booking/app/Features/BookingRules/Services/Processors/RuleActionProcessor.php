<?php

namespace Modules\Booking\app\Features\BookingRules\Services\Processors;

use Modules\Booking\app\Features\BookingRules\Models\BookingAdvancedRule;
use Modules\Booking\app\Features\BookingRules\Models\BookingAdvancedRuleExecution;
use Modules\Booking\app\Features\BookingRules\Services\Processors\Types\RejectBookingProcessor;
use Modules\Booking\app\Features\BookingRules\Services\Processors\Types\RequireApprovalProcessor;
use Modules\Booking\app\Features\BookingRules\Services\Processors\Types\SendNotificationProcessor;

class RuleActionProcessor
{
    public function process(BookingAdvancedRule $rule, array $payload, BookingAdvancedRuleExecution $execution): void
    {
        $actions = $rule->actions;

        foreach ($actions as $action) {
            $this->processSingle($action, $payload, $execution);
        }
    }

    protected function processSingle($action, array $payload, BookingAdvancedRuleExecution $execution): void
    {
        $processor = match ($action->type) {
            'reject' => new RejectBookingProcessor(),
            'require_approval' => new RequireApprovalProcessor(),
            'send_notification' => new SendNotificationProcessor(),
            default => null,
        };

        if ($processor) {
            $processor->process($action, $payload, $execution);
        }
    }
}
