<?php

namespace Modules\ERP\Services;

use Modules\ERP\Models\Activity;
use Modules\ERP\Models\TenantModel;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    /**
     * Log an operational activity.
     */
    public static function log(
        string $action, 
        string $description, 
        ?TenantModel $subject = null, 
        ?int $clientId = null, 
        array $properties = []
    ): ?Activity {
        
        $tenantId = null;
        
        if ($subject) {
            $tenantId = $subject->tenant_id;
            if (!$clientId && method_exists($subject, 'client')) {
                $clientId = $subject->client_id ?? null;
            }
        }
        
        // Fallback to active tenant if possible
        if (!$tenantId) {
            $tenantId = session('tenant_id');
            if (!$tenantId && Auth::check()) {
                $tenant = \Modules\ERP\Models\Tenant::where('user_id', Auth::id())->first();
                if ($tenant) {
                    $tenantId = $tenant->id;
                }
            }
        }

        if (!$tenantId) {
            return null; // Cannot log without tenant context
        }

        $causerId = Auth::check() ? Auth::id() : null;
        if (Auth::guard('erp_team')->check()) {
            $member = Auth::guard('erp_team')->user();
            $causerId = $member->tenant->user_id ?? null;
            $properties = array_merge($properties, ['team_member_id' => $member->id]);
        }

        return Activity::create([
            'tenant_id' => $tenantId,
            'client_id' => $clientId,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject ? $subject->id : null,
            'action' => $action,
            'description' => $description,
            'causer_id' => $causerId,
            'properties' => $properties,
        ]);
    }
}
