<?php

namespace App\Modules\BookingRules\Services\Processors;

use App\Modules\BookingRules\Models\BookingAdvancedRuleAction;
use App\Modules\BookingRules\Models\BookingAdvancedRuleExecution;

interface RuleActionProcessorInterface
{
    public function process(BookingAdvancedRuleAction $action, array $payload, BookingAdvancedRuleExecution $execution): void;
}
