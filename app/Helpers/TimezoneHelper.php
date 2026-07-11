<?php

namespace App\Helpers;

use Carbon\Carbon;

class TimezoneHelper
{
    public static function convertToLocal(?Carbon $date, $format = null, $format_timezone = false, $enableTranslation = null): string
    {
        if (is_null($date)) {
            return __('general.empty');
        }

        $timezone = (auth()->user()->timezone) ?? config('app.timezone');

        $enableTranslation = $enableTranslation !== null ? $enableTranslation : config('timezone.enableTranslation');

        $date->setTimezone($timezone);

        if (is_null($format)) {
            return $enableTranslation ? $date->translatedFormat(config('timezone.format')) : $date->format(config('timezone.format'));
        }

        $formatted_date_time = $enableTranslation ? $date->translatedFormat($format) : $date->format($format);

        if ($format_timezone) {
            return $formatted_date_time.' '.self::formatTimezone($date);
        }

        return $formatted_date_time;
    }

    /**
     * @return Carbon\Carbon
     */
    public static function convertFromLocal($date): Carbon
    {
        return Carbon::parse($date, auth()->user()->timezone)->setTimezone('UTC');
    }

    /**
     * @param  Carbon\Carbon  $date
     */
    private static function formatTimezone(Carbon $date): string
    {
        $timezone = $date->format('e');
        $parts = explode('/', $timezone);

        if (count($parts) > 1) {
            return str_replace('_', ' ', $parts[1]).', '.$parts[0];
        }

        return str_replace('_', ' ', $parts[0]);
    }
}
