<?php

namespace App\Modules\BookingRules\Services\Processors;

use App\Modules\BookingRules\Models\BookingAdvancedRule;
use App\Modules\BookingRules\Models\BookingAdvancedRuleExecution;
use App\Modules\BookingRules\Services\Processors\Types\RejectBookingProcessor;
use App\Modules\BookingRules\Services\Processors\Types\RequireApprovalProcessor;
use App\Modules\BookingRules\Services\Processors\Types\SendNotificationProcessor;

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
