<?php

namespace App\Services;

use App\Models\UserActivity;

class ActivityService
{
    /**
     * Log user activity.
     */
    public static function log(int $userId, string $action, string $description, ?array $metadata = null): void
    {
        if (class_exists(UserActivity::class)) {
            UserActivity::create([
                'user_id' => $userId,
                'action' => $action,
                'description' => $description,
                'metadata' => $metadata ? json_encode($metadata) : null,
                'ip_address' => request()->ip(),
            ]);
        }
    }
}
