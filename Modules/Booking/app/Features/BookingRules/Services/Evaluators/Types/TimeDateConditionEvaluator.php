<?php

namespace Modules\Booking\app\Features\BookingRules\Services\Evaluators\Types;

use Modules\Booking\app\Features\BookingRules\Models\BookingAdvancedRuleCondition;
use Modules\Booking\app\Features\BookingRules\Services\Evaluators\RuleConditionEvaluatorInterface;
use Carbon\Carbon;

class TimeDateConditionEvaluator implements RuleConditionEvaluatorInterface
{
    public function evaluate(BookingAdvancedRuleCondition $condition, array $payload): bool
    {
        $bookingTime = isset($payload['booking_datetime']) 
            ? Carbon::parse($payload['booking_datetime']) 
            : null;

        if (!$bookingTime) {
            return false;
        }

        return match ($condition->operator) {
            'outside_working_hours' => $this->isOutsideWorkingHours($bookingTime, $condition->value),
            'specific_day' => $bookingTime->dayOfWeekIso == ($condition->value['day_of_week'] ?? 0),
            'after_time' => $bookingTime->format('H:i:s') > ($condition->value['time'] ?? '00:00:00'),
            'before_time' => $bookingTime->format('H:i:s') < ($condition->value['time'] ?? '23:59:59'),
            default => false,
        };
    }

    protected function isOutsideWorkingHours(Carbon $time, ?array $settings): bool
    {
        // Example implementation. In reality, check against branch working hours.
        $start = $settings['start'] ?? '09:00:00';
        $end = $settings['end'] ?? '17:00:00';
        
        $currentTime = $time->format('H:i:s');
        return $currentTime < $start || $currentTime > $end;
    }
}
