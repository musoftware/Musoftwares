<?php

namespace Modules\Booking\app\Features\Recurring\Services;

use Carbon\Carbon;

class RecurrenceRuleProcessor
{
    /**
     * Parse and evaluate an RRULE string (RFC 5545).
     * Supported: FREQ (DAILY|WEEKLY|MONTHLY|YEARLY), INTERVAL, COUNT, UNTIL, BYDAY.
     */
    public function generateDates(string $rrule, Carbon $startsAt, Carbon $endsAtLimit)
    {
        $dates = [];
        $current = $startsAt->copy();
        
        $rules = $this->parseRrule($rrule);
        
        $freq = $rules['FREQ'] ?? 'WEEKLY';
        $interval = (int) ($rules['INTERVAL'] ?? 1);
        $count = isset($rules['COUNT']) ? (int) $rules['COUNT'] : null;
        
        if (isset($rules['UNTIL'])) {
            try {
                $until = Carbon::parse($rules['UNTIL']);
                if ($until->lt($endsAtLimit)) {
                    $endsAtLimit = $until;
                }
            } catch (\Exception $e) {}
        }

        $byDay = isset($rules['BYDAY']) ? explode(',', $rules['BYDAY']) : null;
        $dayMap = ['SU' => 0, 'MO' => 1, 'TU' => 2, 'WE' => 3, 'TH' => 4, 'FR' => 5, 'SA' => 6];

        $occurrences = 0;

        while ($current->lte($endsAtLimit)) {
            if ($count !== null && $occurrences >= $count) {
                break;
            }

            // If BYDAY is set, we must match the day of the week
            $matchesDay = true;
            if ($byDay && $freq !== 'DAILY') {
                $currentDayCode = array_search($current->dayOfWeek, $dayMap);
                $matchesDay = in_array($currentDayCode, $byDay) || in_array(strtoupper(substr($current->englishDayOfWeek, 0, 2)), $byDay);
            }

            if ($matchesDay) {
                $dates[] = $current->copy();
                $occurrences++;
            }

            // Advance time
            switch ($freq) {
                case 'DAILY':
                    $current->addDays($interval);
                    break;
                case 'WEEKLY':
                    // If we are filtering by BYDAY, we just advance one day at a time, 
                    // and handle the interval jump when we hit the end of the week.
                    if ($byDay) {
                        $current->addDay();
                        // If we hit Sunday (0), we just finished a week, jump interval - 1 weeks
                        if ($current->dayOfWeek === 0 && $interval > 1) {
                            $current->addWeeks($interval - 1);
                        }
                    } else {
                        $current->addWeeks($interval);
                    }
                    break;
                case 'MONTHLY':
                    $current->addMonthsNoOverflow($interval);
                    break;
                case 'YEARLY':
                    $current->addYearsNoOverflow($interval);
                    break;
                default:
                    $current->addWeeks($interval);
                    break;
            }
        }

        return $dates;
    }

    private function parseRrule(string $rrule): array
    {
        $parsed = [];
        // Remove RRULE: prefix if present
        $rrule = str_replace('RRULE:', '', strtoupper($rrule));
        $parts = explode(';', $rrule);
        
        foreach ($parts as $part) {
            $kv = explode('=', $part);
            if (count($kv) === 2) {
                $parsed[trim($kv[0])] = trim($kv[1]);
            }
        }
        
        return $parsed;
    }
}
