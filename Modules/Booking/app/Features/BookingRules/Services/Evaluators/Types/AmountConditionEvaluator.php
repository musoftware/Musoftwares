<?php

namespace Modules\Booking\app\Features\BookingRules\Services\Evaluators\Types;

use Modules\Booking\app\Features\BookingRules\Models\BookingAdvancedRuleCondition;
use Modules\Booking\app\Features\BookingRules\Services\Evaluators\RuleConditionEvaluatorInterface;

class AmountConditionEvaluator implements RuleConditionEvaluatorInterface
{
    public function evaluate(BookingAdvancedRuleCondition $condition, array $payload): bool
    {
        $payloadAmount = $payload['amount'] ?? 0;
        $conditionValue = $condition->value['amount'] ?? 0;
        
        return match ($condition->operator) {
            'equals' => $payloadAmount == $conditionValue,
            'greater_than' => $payloadAmount > $conditionValue,
            'less_than' => $payloadAmount < $conditionValue,
            'greater_than_or_equal' => $payloadAmount >= $conditionValue,
            'less_than_or_equal' => $payloadAmount <= $conditionValue,
            default => false,
        };
    }
}
