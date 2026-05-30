<?php

namespace Modules\Booking\app\Features\BookingRules\Services\Evaluators\Types;

use Modules\Booking\app\Features\BookingRules\Models\BookingAdvancedRuleCondition;
use Modules\Booking\app\Features\BookingRules\Services\Evaluators\RuleConditionEvaluatorInterface;

class CustomerNoShowEvaluator implements RuleConditionEvaluatorInterface
{
    public function evaluate(BookingAdvancedRuleCondition $condition, array $payload): bool
    {
        // Mock payload value for customer no-shows
        // In reality, this would query the DB: 
        // Booking::where('customer_id', $payload['customer_id'])->where('status', 'no_show')->count()
        $customerNoShows = $payload['customer_no_show_count'] ?? 0;
        $threshold = $condition->value['count'] ?? 3;

        return match ($condition->operator) {
            'equals' => $customerNoShows == $threshold,
            'greater_than' => $customerNoShows > $threshold,
            'greater_than_or_equal' => $customerNoShows >= $threshold,
            default => false,
        };
    }
}
