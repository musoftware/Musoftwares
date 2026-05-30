<?php

namespace Modules\Booking\app\Features\BookingRules\Services\Evaluators;

use Modules\Booking\app\Features\BookingRules\Models\BookingAdvancedRuleCondition;

interface RuleConditionEvaluatorInterface
{
    public function evaluate(BookingAdvancedRuleCondition $condition, array $payload): bool;
}
