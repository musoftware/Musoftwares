<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Platform-wide 15-month fiscal visibility window evaluated in Africa/Cairo.
 *
 * From Jan 1 through Mar 31 of year Y, records from Jan 1 of year (Y - 1) remain visible
 * (up to 15 months). Starting Apr 1 of year Y, visibility advances to Jan 1 of year Y.
 */
final class LegalWindow
{
    public const BUSINESS_TIMEZONE = 'Africa/Cairo';

    public const GRACE_END_MONTH = 3;

    public static function cutoffCairo(?CarbonInterface $now = null): Carbon
    {
        $cairoNow = ($now ? Carbon::instance($now) : Carbon::now())
            ->copy()
            ->setTimezone(self::BUSINESS_TIMEZONE);

        $startYear = $cairoNow->month <= self::GRACE_END_MONTH
            ? ($cairoNow->year - 1)
            : $cairoNow->year;

        return Carbon::create($startYear, 1, 1, 0, 0, 0, self::BUSINESS_TIMEZONE);
    }

    public static function cutoffDatabase(?CarbonInterface $now = null): Carbon
    {
        return self::cutoffCairo($now)
            ->copy()
            ->setTimezone((string) config('app.timezone', 'UTC'));
    }

    public static function cutoffDateString(?CarbonInterface $now = null): string
    {
        return self::cutoffCairo($now)->toDateString();
    }

    public static function earliestVisibleYear(?CarbonInterface $now = null): int
    {
        return self::cutoffCairo($now)->year;
    }

    public static function clampYear(int $year, ?CarbonInterface $now = null): int
    {
        return max($year, self::earliestVisibleYear($now));
    }

    public static function clampFromDate(CarbonInterface|string|null $date, ?CarbonInterface $now = null): Carbon
    {
        $cutoff = self::cutoffCairo($now);

        if ($date === null || $date === '') {
            return $cutoff;
        }

        $parsed = $date instanceof CarbonInterface
            ? Carbon::instance($date)->copy()->setTimezone(self::BUSINESS_TIMEZONE)->startOfDay()
            : Carbon::parse((string) $date, self::BUSINESS_TIMEZONE)->startOfDay();

        return $parsed->lt($cutoff) ? $cutoff : $parsed;
    }
}

