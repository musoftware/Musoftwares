<?php

namespace App\Services\Tools;

use DateTime;
use DateTimeZone;
use DateInterval;
use DatePeriod;

class DateToolsService
{
    /**
     * Calculate age from birthdate
     */
    public function calculateAge(string $birthdate): array
    {
        try {
            $birth = new DateTime($birthdate);
            $today = new DateTime();
            $age = $today->diff($birth);
            
            return [
                'success' => true,
                'birthdate' => $birthdate,
                'age_years' => $age->y,
                'age_months' => $age->m,
                'age_days' => $age->d,
                'total_days' => $age->days,
                'next_birthday' => $this->getNextBirthday($birthdate)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Invalid birthdate format',
                'birthdate' => $birthdate
            ];
        }
    }

    /**
     * Calculate difference between two dates
     */
    public function calculateDateDifference(string $startDate, string $endDate): array
    {
        try {
            $start = new DateTime($startDate);
            $end = new DateTime($endDate);
            $diff = $start->diff($end);
            
            $totalDays = $start->diff($end)->days;
            $isNegative = $start > $end;
            
            return [
                'success' => true,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'years' => $diff->y,
                'months' => $diff->m,
                'days' => $diff->d,
                'total_days' => $totalDays,
                'total_weeks' => round($totalDays / 7, 2),
                'is_negative' => $isNegative,
                'weekdays' => $this->countWeekdays($start, $end)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Invalid date format',
                'start_date' => $startDate,
                'end_date' => $endDate
            ];
        }
    }

    /**
     * Compute time difference between two times
     */
    public function computeTimeDifference(string $startTime, string $endTime, ?string $timezone = 'UTC'): array
    {
        try {
            $tz = new DateTimeZone($timezone);
            $start = new DateTime($startTime, $tz);
            $end = new DateTime($endTime, $tz);
            
            $diff = $start->diff($end);
            $totalSeconds = $end->getTimestamp() - $start->getTimestamp();
            
            return [
                'success' => true,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'timezone' => $timezone,
                'hours' => $diff->h,
                'minutes' => $diff->i,
                'seconds' => $diff->s,
                'total_seconds' => $totalSeconds,
                'total_minutes' => round($totalSeconds / 60, 2),
                'total_hours' => round($totalSeconds / 3600, 2),
                'is_negative' => $totalSeconds < 0
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Invalid time format or timezone',
                'start_time' => $startTime,
                'end_time' => $endTime,
                'timezone' => $timezone
            ];
        }
    }

    /**
     * Add or subtract time from a date
     */
    public function addOrSubtractDate(
        string $date,
        int $years,
        int $months,
        int $days,
        int $hours,
        int $minutes,
        string $operation = 'add',
        ?string $timezone = 'UTC'
    ): array {
        try {
            $tz = new DateTimeZone($timezone);
            $dateTime = new DateTime($date, $tz);
            
            $interval = new DateInterval("P{$years}Y{$months}M{$days}DT{$hours}H{$minutes}M");
            
            if ($operation === 'subtract') {
                $dateTime->sub($interval);
            } else {
                $dateTime->add($interval);
            }
            
            return [
                'success' => true,
                'original_date' => $date,
                'operation' => $operation,
                'years' => $years,
                'months' => $months,
                'days' => $days,
                'hours' => $hours,
                'minutes' => $minutes,
                'result_date' => $dateTime->format('Y-m-d H:i:s'),
                'timezone' => $timezone
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Invalid date format or operation',
                'original_date' => $date,
                'operation' => $operation
            ];
        }
    }

    /**
     * Calculate working days between two dates
     */
    public function calculateWorkingDays(
        string $startDate,
        string $endDate,
        array $holidays = [],
        array $workingWeekdays = [1,2,3,4,5],
        bool $includeEnd = true
    ): array {
        try {
            $start = new DateTime($startDate);
            $end = new DateTime($endDate);
            
            if ($start > $end) {
                return [
                    'success' => false,
                    'error' => 'Start date cannot be after end date',
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ];
            }
            
            $workingDays = 0;
            $current = clone $start;
            
            while ($current <= $end) {
                $dayOfWeek = (int)$current->format('N');
                $dateString = $current->format('Y-m-d');
                
                if (in_array($dayOfWeek, $workingWeekdays) && !in_array($dateString, $holidays)) {
                    $workingDays++;
                }
                
                $current->add(new DateInterval('P1D'));
            }
            
            if (!$includeEnd && $current->format('Y-m-d') === $end->format('Y-m-d')) {
                $workingDays--;
            }
            
            return [
                'success' => true,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'working_days' => $workingDays,
                'total_days' => $start->diff($end)->days + 1,
                'weekends' => $this->countWeekends($start, $end),
                'holidays' => count($holidays),
                'working_weekdays' => $workingWeekdays
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Invalid date format',
                'start_date' => $startDate,
                'end_date' => $endDate
            ];
        }
    }

    /**
     * Find weekday of a date
     */
    public function findWeekday(string $date): array
    {
        try {
            $dateTime = new DateTime($date);
            $weekday = $dateTime->format('l');
            $weekdayNumber = (int)$dateTime->format('N');
            
            return [
                'success' => true,
                'date' => $date,
                'weekday' => $weekday,
                'weekday_number' => $weekdayNumber,
                'is_weekend' => in_array($weekdayNumber, [6, 7]),
                'is_weekday' => in_array($weekdayNumber, [1, 2, 3, 4, 5])
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Invalid date format',
                'date' => $date
            ];
        }
    }

    /**
     * Find week number of a date
     */
    public function findWeekNumber(string $date): array
    {
        try {
            $dateTime = new DateTime($date);
            $weekNumber = (int)$dateTime->format('W');
            $year = (int)$dateTime->format('Y');
            
            return [
                'success' => true,
                'date' => $date,
                'week_number' => $weekNumber,
                'year' => $year,
                'iso_week' => $dateTime->format('o-W'),
                'week_start' => $this->getWeekStart($dateTime)->format('Y-m-d'),
                'week_end' => $this->getWeekEnd($dateTime)->format('Y-m-d')
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Invalid date format',
                'date' => $date
            ];
        }
    }

    /**
     * Check if a year is a leap year
     */
    public function isLeapYear(int $year): array
    {
        $isLeap = ($year % 4 == 0 && $year % 100 != 0) || ($year % 400 == 0);
        
        return [
            'success' => true,
            'year' => $year,
            'is_leap_year' => $isLeap,
            'days_in_february' => $isLeap ? 29 : 28,
            'days_in_year' => $isLeap ? 366 : 365,
            'next_leap_year' => $this->findNextLeapYear($year)
        ];
    }

    /**
     * Countdown to a target date/time
     */
    public function countdownTo(string $targetDateTime, string $timezone = 'UTC'): array
    {
        try {
            $tz = new DateTimeZone($timezone);
            $target = new DateTime($targetDateTime, $tz);
            $now = new DateTime('now', $tz);
            
            if ($target <= $now) {
                return [
                    'success' => false,
                    'error' => 'Target date/time is in the past',
                    'target_datetime' => $targetDateTime,
                    'timezone' => $timezone
                ];
            }
            
            $diff = $now->diff($target);
            $totalSeconds = $target->getTimestamp() - $now->getTimestamp();
            
            return [
                'success' => true,
                'target_datetime' => $targetDateTime,
                'timezone' => $timezone,
                'years' => $diff->y,
                'months' => $diff->m,
                'days' => $diff->d,
                'hours' => $diff->h,
                'minutes' => $diff->i,
                'seconds' => $diff->s,
                'total_seconds' => $totalSeconds,
                'total_days' => round($totalSeconds / 86400, 2),
                'is_active' => true
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Invalid target date/time format',
                'target_datetime' => $targetDateTime,
                'timezone' => $timezone
            ];
        }
    }

    /**
     * World clock for multiple timezones
     */
    public function worldClock(array $timezones): array
    {
        $results = [];
        $now = new DateTime('now', new DateTimeZone('UTC'));
        
        foreach ($timezones as $timezone) {
            try {
                $tz = new DateTimeZone($timezone);
                $localTime = new DateTime('now', $tz);
                
                $results[] = [
                    'timezone' => $timezone,
                    'local_time' => $localTime->format('Y-m-d H:i:s'),
                    'formatted_time' => $localTime->format('l, F j, Y g:i A T'),
                    'utc_offset' => $localTime->format('P'),
                    'is_dst' => $localTime->format('I') == '1'
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'timezone' => $timezone,
                    'error' => 'Invalid timezone'
                ];
            }
        }
        
        return [
            'success' => true,
            'timezones' => $results,
            'utc_time' => $now->format('Y-m-d H:i:s'),
            'count' => count($timezones)
        ];
    }

    /**
     * Convert date format
     */
    public function convertDateFormat(string $input, string $fromFormat, string $toFormat, string $timezone = 'UTC'): array
    {
        try {
            $tz = new DateTimeZone($timezone);
            $date = DateTime::createFromFormat($fromFormat, $input, $tz);
            
            if (!$date) {
                return [
                    'success' => false,
                    'error' => 'Invalid input date or format',
                    'input' => $input,
                    'from_format' => $fromFormat,
                    'to_format' => $toFormat
                ];
            }
            
            return [
                'success' => true,
                'input' => $input,
                'from_format' => $fromFormat,
                'to_format' => $toFormat,
                'converted' => $date->format($toFormat),
                'timezone' => $timezone,
                'iso_format' => $date->format('c')
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Date conversion failed',
                'input' => $input,
                'from_format' => $fromFormat,
                'to_format' => $toFormat
            ];
        }
    }

    /**
     * Lookup timezone information
     */
    public function lookupTimezone(string $query): array
    {
        $timezones = timezone_identifiers_list();
        $matches = [];
        
        foreach ($timezones as $timezone) {
            if (stripos($timezone, $query) !== false) {
                try {
                    $tz = new DateTimeZone($timezone);
                    $now = new DateTime('now', $tz);
                    
                    $matches[] = [
                        'timezone' => $timezone,
                        'utc_offset' => $now->format('P'),
                        'is_dst' => $now->format('I') == '1',
                        'current_time' => $now->format('Y-m-d H:i:s T')
                    ];
                } catch (\Exception $e) {
                    // Skip invalid timezones
                }
            }
        }
        
        return [
            'success' => true,
            'query' => $query,
            'matches' => $matches,
            'count' => count($matches)
        ];
    }

    /**
     * Calculate sunrise and sunset times
     */
    public function sunriseSunset(float $lat, float $lng, string $date, string $timezone = 'UTC'): array
    {
        try {
            $tz = new DateTimeZone($timezone);
            $dateTime = new DateTime($date, $tz);
            $timestamp = $dateTime->getTimestamp();
            
            $sunrise = date_sunrise($timestamp, SUNFUNCS_RET_TIMESTAMP, $lat, $lng);
            $sunset = date_sunset($timestamp, SUNFUNCS_RET_TIMESTAMP, $lat, $lng);
            
            if ($sunrise === false || $sunset === false) {
                return [
                    'success' => false,
                    'error' => 'Unable to calculate sunrise/sunset for given coordinates',
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'date' => $date
                ];
            }
            
            $sunriseTime = new DateTime('@' . $sunrise, $tz);
            $sunsetTime = new DateTime('@' . $sunset, $tz);
            $dayLength = $sunset - $sunrise;
            
            return [
                'success' => true,
                'latitude' => $lat,
                'longitude' => $lng,
                'date' => $date,
                'timezone' => $timezone,
                'sunrise' => $sunriseTime->format('H:i:s'),
                'sunset' => $sunsetTime->format('H:i:s'),
                'day_length_hours' => round($dayLength / 3600, 2),
                'day_length_minutes' => round($dayLength / 60),
                'sunrise_timestamp' => $sunrise,
                'sunset_timestamp' => $sunset
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Invalid parameters for sunrise/sunset calculation',
                'latitude' => $lat,
                'longitude' => $lng,
                'date' => $date
            ];
        }
    }

    /**
     * Calculate next birthday
     */
    public function nextBirthday(string $birthdate, string $timezone = 'UTC'): array
    {
        try {
            $tz = new DateTimeZone($timezone);
            $birth = new DateTime($birthdate, $tz);
            $now = new DateTime('now', $tz);
            
            $nextBirthday = new DateTime($now->format('Y') . '-' . $birth->format('m-d'), $tz);
            
            if ($nextBirthday <= $now) {
                $nextBirthday->add(new DateInterval('P1Y'));
            }
            
            $diff = $now->diff($nextBirthday);
            $totalDays = $now->diff($nextBirthday)->days;
            
            return [
                'success' => true,
                'birthdate' => $birthdate,
                'next_birthday' => $nextBirthday->format('Y-m-d'),
                'days_until' => $totalDays,
                'months_until' => $diff->m,
                'years_until' => $diff->y,
                'age_at_birthday' => $now->diff($birth)->y + ($diff->y > 0 ? 1 : 0),
                'timezone' => $timezone
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Invalid birthdate format',
                'birthdate' => $birthdate
            ];
        }
    }

    /**
     * Generate date range
     */
    public function generateDateRange(string $startDate, string $endDate, string $step = 'P1D'): array
    {
        try {
            $start = new DateTime($startDate);
            $end = new DateTime($endDate);
            $interval = new DateInterval($step);
            $period = new DatePeriod($start, $interval, $end);
            
            $dates = [];
            foreach ($period as $date) {
                $dates[] = $date->format('Y-m-d');
            }
            
            return [
                'success' => true,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'step' => $step,
                'dates' => $dates,
                'count' => count($dates)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Invalid date range parameters',
                'start_date' => $startDate,
                'end_date' => $endDate,
                'step' => $step
            ];
        }
    }

    /**
     * Generate calendar for a specific month/year
     */
    public function generateCalendar(int $year, int $month): array
    {
        try {
            $firstDay = new DateTime("{$year}-{$month}-01");
            $lastDay = new DateTime("{$year}-{$month}-" . $firstDay->format('t'));
            
            $calendar = [];
            $current = clone $firstDay;
            $current->modify('first day of this month');
            $current->modify('monday this week');
            
            $weeks = [];
            $week = [];
            
            while ($current <= $lastDay || count($week) < 7) {
                $week[] = [
                    'date' => $current->format('Y-m-d'),
                    'day' => (int)$current->format('j'),
                    'is_current_month' => $current->format('n') == $month,
                    'is_today' => $current->format('Y-m-d') == date('Y-m-d'),
                    'weekday' => (int)$current->format('N')
                ];
                
                if (count($week) == 7) {
                    $weeks[] = $week;
                    $week = [];
                }
                
                $current->add(new DateInterval('P1D'));
            }
            
            if ($week) {
                $weeks[] = $week;
            }
            
            return [
                'success' => true,
                'year' => $year,
                'month' => $month,
                'month_name' => $firstDay->format('F'),
                'days_in_month' => (int)$firstDay->format('t'),
                'first_day_weekday' => (int)$firstDay->format('N'),
                'weeks' => $weeks,
                'week_count' => count($weeks)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Invalid year or month',
                'year' => $year,
                'month' => $month
            ];
        }
    }

    /**
     * Get DST transitions for a timezone
     */
    public function dstTransitions(string $timezone, int $year): array
    {
        try {
            $tz = new DateTimeZone($timezone);
            $transitions = $tz->getTransitions(strtotime("{$year}-01-01"), strtotime("{$year}-12-31"));
            
            $dstTransitions = [];
            foreach ($transitions as $transition) {
                if ($transition['isdst'] !== null) {
                    $dstTransitions[] = [
                        'timestamp' => $transition['ts'],
                        'datetime' => date('Y-m-d H:i:s T', $transition['ts']),
                        'offset' => $transition['offset'],
                        'isdst' => $transition['isdst'],
                        'abbr' => $transition['abbr']
                    ];
                }
            }
            
            return [
                'success' => true,
                'timezone' => $timezone,
                'year' => $year,
                'transitions' => $dstTransitions,
                'count' => count($dstTransitions)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Invalid timezone or year',
                'timezone' => $timezone,
                'year' => $year
            ];
        }
    }

    /**
     * Convert Gregorian to Julian Day
     */
    public function gregorianToJd(int $year, int $month, int $day): int
    {
        if ($month <= 2) {
            $year--;
            $month += 12;
        }
        
        $a = intval($year / 100);
        $b = 2 - $a + intval($a / 4);
        
        return intval(365.25 * ($year + 4716)) + intval(30.6001 * ($month + 1)) + $day + $b - 1524.5;
    }

    /**
     * Convert Julian Day to Gregorian
     */
    public function jdToGregorian(int $jd): array
    {
        $jd += 0.5;
        $z = intval($jd);
        $f = $jd - $z;
        
        if ($z < 2299161) {
            $a = $z;
        } else {
            $alpha = intval(($z - 1867216.25) / 36524.25);
            $a = $z + 1 + $alpha - intval($alpha / 4);
        }
        
        $b = $a + 1524;
        $c = intval(($b - 122.1) / 365.25);
        $d = intval(365.25 * $c);
        $e = intval(($b - $d) / 30.6001);
        
        $day = $b - $d - intval(30.6001 * $e) + $f;
        $month = $e < 14 ? $e - 1 : $e - 13;
        $year = $month < 3 ? $c - 4715 : $c - 4716;
        
        return [
            'year' => $year,
            'month' => $month,
            'day' => intval($day)
        ];
    }

    /**
     * Convert Julian calendar
     */
    public function convertJulian(string $mode, array $input): array
    {
        try {
            if ($mode === 'to_julian') {
                $jd = $this->gregorianToJd($input['year'], $input['month'], $input['day']);
                return [
                    'success' => true,
                    'mode' => $mode,
                    'input' => $input,
                    'julian_day' => $jd
                ];
            } elseif ($mode === 'to_gregorian') {
                $gregorian = $this->jdToGregorian($input['julian_day']);
                return [
                    'success' => true,
                    'mode' => $mode,
                    'input' => $input,
                    'gregorian' => $gregorian
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Invalid mode. Use "to_julian" or "to_gregorian"',
                    'mode' => $mode
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Conversion failed',
                'mode' => $mode,
                'input' => $input
            ];
        }
    }

    /**
     * Convert ordinal date
     */
    public function convertOrdinalDate(string $input, string $mode = 'to_calendar'): array
    {
        try {
            if ($mode === 'to_calendar') {
                // Input: YYYY-DDD
                list($year, $dayOfYear) = explode('-', $input);
                $date = new DateTime("{$year}-01-01");
                $date->add(new DateInterval('P' . ($dayOfYear - 1) . 'D'));
                
                return [
                    'success' => true,
                    'mode' => $mode,
                    'ordinal_date' => $input,
                    'calendar_date' => $date->format('Y-m-d'),
                    'month' => (int)$date->format('n'),
                    'day' => (int)$date->format('j')
                ];
            } else {
                // Input: YYYY-MM-DD
                $date = new DateTime($input);
                $year = $date->format('Y');
                $dayOfYear = $date->format('z') + 1;
                
                return [
                    'success' => true,
                    'mode' => $mode,
                    'calendar_date' => $input,
                    'ordinal_date' => "{$year}-{$dayOfYear}",
                    'day_of_year' => $dayOfYear
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Invalid date format',
                'input' => $input,
                'mode' => $mode
            ];
        }
    }

    /**
     * Calculate duration
     */
    public function calculateDuration(string $base, string $duration, string $operation = 'add', string $timezone = 'UTC'): array
    {
        try {
            $tz = new DateTimeZone($timezone);
            $baseDate = new DateTime($base, $tz);
            $interval = new DateInterval($duration);
            
            if ($operation === 'subtract') {
                $baseDate->sub($interval);
            } else {
                $baseDate->add($interval);
            }
            
            return [
                'success' => true,
                'base_date' => $base,
                'duration' => $duration,
                'operation' => $operation,
                'result' => $baseDate->format('Y-m-d H:i:s'),
                'timezone' => $timezone
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Invalid duration or operation',
                'base_date' => $base,
                'duration' => $duration,
                'operation' => $operation
            ];
        }
    }

    // Private helper methods

    private function getNextBirthday(string $birthdate): string
    {
        try {
            $birth = new DateTime($birthdate);
            $now = new DateTime();
            $nextBirthday = new DateTime($now->format('Y') . '-' . $birth->format('m-d'));
            
            if ($nextBirthday <= $now) {
                $nextBirthday->add(new DateInterval('P1Y'));
            }
            
            return $nextBirthday->format('Y-m-d');
        } catch (\Exception $e) {
            return '';
        }
    }

    private function countWeekdays(DateTime $start, DateTime $end): int
    {
        $count = 0;
        $current = clone $start;
        
        while ($current <= $end) {
            $dayOfWeek = (int)$current->format('N');
            if ($dayOfWeek >= 1 && $dayOfWeek <= 5) {
                $count++;
            }
            $current->add(new DateInterval('P1D'));
        }
        
        return $count;
    }

    private function countWeekends(DateTime $start, DateTime $end): int
    {
        $count = 0;
        $current = clone $start;
        
        while ($current <= $end) {
            $dayOfWeek = (int)$current->format('N');
            if ($dayOfWeek == 6 || $dayOfWeek == 7) {
                $count++;
            }
            $current->add(new DateInterval('P1D'));
        }
        
        return $count;
    }

    private function getWeekStart(DateTime $date): DateTime
    {
        $weekStart = clone $date;
        $weekStart->modify('monday this week');
        return $weekStart;
    }

    private function getWeekEnd(DateTime $date): DateTime
    {
        $weekEnd = clone $date;
        $weekEnd->modify('sunday this week');
        return $weekEnd;
    }

    private function findNextLeapYear(int $year): int
    {
        for ($i = $year + 1; $i <= $year + 4; $i++) {
            if (($i % 4 == 0 && $i % 100 != 0) || ($i % 400 == 0)) {
                return $i;
            }
        }
        return $year + 4;
    }
}
