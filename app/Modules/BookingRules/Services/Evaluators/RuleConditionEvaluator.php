<?php

namespace App\Modules\BookingRules\Services\Evaluators;

use App\Modules\BookingRules\Models\BookingAdvancedRule;
use App\Modules\BookingRules\Services\Evaluators\Types\AmountConditionEvaluator;
use App\Modules\BookingRules\Services\Evaluators\Types\TimeDateConditionEvaluator;
use App\Modules\BookingRules\Services\Evaluators\Types\CustomerNoShowEvaluator;

class RuleConditionEvaluator
{
    /**
     * Evaluates all conditions for a given rule against the payload.
     * Implements AND/OR logic via group_id.
     */
    public function evaluate(BookingAdvancedRule $rule, array $payload): bool
    {
        $conditions = $rule->conditions;
        if ($conditions->isEmpty()) {
            return true; // No conditions = always matches
        }

        $groupedConditions = $conditions->groupBy('group_id');
        
        foreach ($groupedConditions as $groupId => $group) {
            $groupMatched = false;
            foreach ($group as $condition) {
                if ($this->evaluateSingle($condition, $payload)) {
                    $groupMatched = true;
                    break; // OR logic within a group
                }
            }
            if (!$groupMatched) {
                return false; // AND logic between groups
            }
        }

        return true;
    }

    protected function evaluateSingle($condition, array $payload): bool
    {
        $evaluator = match ($condition->type) {
            'amount' => new AmountConditionEvaluator(),
            'time_date' => new TimeDateConditionEvaluator(),
            'customer_no_show' => new CustomerNoShowEvaluator(),
            default => null,
        };

        if ($evaluator) {
            return $evaluator->evaluate($condition, $payload);
        }

        // Unknown condition types might fail safe (return true or false based on strategy)
        return false;
    }
}
