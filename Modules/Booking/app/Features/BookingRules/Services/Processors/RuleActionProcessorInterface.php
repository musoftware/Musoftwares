<?php

namespace Modules\Booking\app\Features\BookingRules\Services\Processors;

use Modules\Booking\app\Features\BookingRules\Models\BookingAdvancedRuleAction;
use Modules\Booking\app\Features\BookingRules\Models\BookingAdvancedRuleExecution;

interface RuleActionProcessorInterface
{
    public function process(BookingAdvancedRuleAction $action, array $payload, BookingAdvancedRuleExecution $execution): void;
}
