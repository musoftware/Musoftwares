<?php

namespace App\Modules\BookingRules\Services\Evaluators;

use App\Modules\BookingRules\Models\BookingAdvancedRuleCondition;

interface RuleConditionEvaluatorInterface
{
    public function evaluate(BookingAdvancedRuleCondition $condition, array $payload): bool;
}
