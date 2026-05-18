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
            $tenantId = session('tenant_id') ?? Auth::user()?->tenant_id;
        }

        if (!$tenantId) {
            return null; // Cannot log without tenant context
        }

        return Activity::create([
            'tenant_id' => $tenantId,
            'client_id' => $clientId,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject ? $subject->id : null,
            'action' => $action,
            'description' => $description,
            'causer_id' => Auth::id(),
            'properties' => $properties,
        ]);
    }
}
