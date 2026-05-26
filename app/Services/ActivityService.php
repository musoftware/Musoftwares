<?php

namespace App\Services;

use App\Models\UserActivity;
use Illuminate\Support\Facades\Auth;

class ActivityService
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
                    } catch (\Exception $e) {}
                }
                
                UserActivity::create([
                    'user_id' => $userIdVal,
                    'activity_date' => date('Y-m-d'),
                    'total_seconds' => 0,
                    'ip' => $ip,
                    'iso_code' => $isoCode
                ]);
            }
            return;
        }

        // New named call
        $userId = $userId ?? Auth::id();

        // 1. ERP logging
        if ($workspace === 'erp') {
            $tenantId = null;
            $clientId = null;
            if ($subject && isset($subject->tenant_id)) {
                $tenantId = $subject->tenant_id;
            }
            if ($subject && isset($subject->client_id)) {
                $clientId = $subject->client_id;
            }
            if (!$tenantId) {
                $tenantId = session('tenant_id');
            }
            if (!$tenantId && Auth::check()) {
                $tenant = \Modules\ERP\Models\Tenant::where('user_id', Auth::id())->first();
                if ($tenant) {
                    $tenantId = $tenant->id;
                }
            }

            if ($tenantId && class_exists(\Modules\ERP\Models\Activity::class)) {
                \Modules\ERP\Models\Activity::create([
                    'tenant_id' => $tenantId,
                    'client_id' => $clientId,
                    'subject_type' => $subject ? get_class($subject) : null,
                    'subject_id' => $subject ? $subject->id : null,
                    'action' => $event ?? 'unknown',
                    'description' => $description ?? '',
                    'causer_id' => $userId,
                    'properties' => $properties,
                ]);
            }
        }

        // 2. CRM logging
        if ($workspace === 'crm') {
            if (class_exists(\Modules\CRM\Models\Activity::class)) {
                $workspaceId = session('crm_workspace_id');
                if (!$workspaceId && Auth::check()) {
                    $crmWorkspace = \Modules\CRM\Models\Workspace::where('user_id', Auth::id())->first();
                    if ($crmWorkspace) {
                        $workspaceId = $crmWorkspace->id;
                    }
                }
                if ($workspaceId) {
                    \Modules\CRM\Models\Activity::create([
                        'workspace_id' => $workspaceId,
                        'user_id' => $userId,
                        'event' => $event ?? 'unknown',
                        'entity_type' => $subject ? get_class($subject) : null,
                        'entity_id' => $subject ? $subject->id : null,
                        'metadata' => $properties,
                    ]);
                }
            }
        }

        // Always log to core UserActivity table for general usage tracking
        if (class_exists(UserActivity::class) && $userId) {
            $ip = request()->ip() ?? '127.0.0.1';
            $isoCode = 'US';
            if (function_exists('geoip')) {
                try {
                    $location = geoip()->getLocation($ip);
                    $isoCode = $location->iso_code ?? 'US';
                } catch (\Exception $e) {}
            }
            
            // Check if already recorded for today to prevent duplicates
            $date = date('Y-m-d');
            $exists = UserActivity::where('user_id', $userId)
                ->where('activity_date', $date)
                ->exists();
            if (!$exists) {
                UserActivity::create([
                    'user_id' => $userId,
                    'activity_date' => $date,
                    'total_seconds' => 0,
                    'ip' => $ip,
                    'iso_code' => $isoCode
                ]);
            }
        }
    }
}
