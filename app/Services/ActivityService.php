<?php

namespace App\Services;

use App\Models\UserActivity;
use Illuminate\Support\Facades\Auth;

class ActivityService extends BaseService
{
    /**
     * Log user activity. Supports both legacy positional logging and new named/event-driven logging.
     */
    public static function log(
        $event = null,
        $description = null,
        $subject = null,
        $workspace = 'erp',
        array $properties = [],
        $userId = null,
        $action = null,
        $metadata = null
    ): void {
        // Detect positional/legacy call: log(int $userId, string $action, string $description, ?array $metadata = null)
        if (is_int($event) || (is_string($event) && is_numeric($event))) {
            $userIdVal = (int) $event;
            $actionVal = $description;
            $descriptionVal = $subject;
            $metadataVal = is_array($workspace) ? $workspace : null;

            if (class_exists(UserActivity::class)) {
                $ip = request()->ip() ?? '127.0.0.1';
                $isoCode = 'US';
                if (function_exists('geoip')) {
                    try {
                        $location = geoip()->getLocation($ip);
                        $isoCode = $location->iso_code ?? 'US';
                    } catch (\Exception $e) {
                    }
                }

                UserActivity::create([
                    'user_id' => $userIdVal,
                    'activity_date' => date('Y-m-d'),
                    'total_seconds' => 0,
                    'ip' => $ip,
                    'iso_code' => $isoCode,
                ]);
            }

            return;
        }

        // New named call
        $userId = $userId ?? Auth::id();

        // ERP module logs its own activities internally via Modules\ERP\Services\ActivityLogger.
        // The main ActivityService does NOT write to the ERP module.

        // Always log to core UserActivity table for general usage tracking
        if (class_exists(UserActivity::class) && $userId) {
            $ip = request()->ip() ?? '127.0.0.1';
            $isoCode = 'US';
            if (function_exists('geoip')) {
                try {
                    $location = geoip()->getLocation($ip);
                    $isoCode = $location->iso_code ?? 'US';
                } catch (\Exception $e) {
                }
            }

            // Check if already recorded for today to prevent duplicates
            $date = date('Y-m-d');
            $exists = UserActivity::where('user_id', $userId)
                ->where('activity_date', $date)
                ->exists();
            if (! $exists) {
                UserActivity::create([
                    'user_id' => $userId,
                    'activity_date' => $date,
                    'total_seconds' => 0,
                    'ip' => $ip,
                    'iso_code' => $isoCode,
                ]);
            }
        }
    }
}
