<?php

namespace App\Modules\BookingPriority\Services;

use App\Modules\BookingPriority\Models\BookingPriorityAssignment;
use App\Modules\BookingPriority\Models\BookingPriorityLevel;
use App\Modules\BookingPriority\Models\BookingPriorityLog;

class PriorityAssignmentEngine
{
    public function escalate(int $tenantId, int $bookingId, string $reason, ?int $userId = null): void
    {
        // Find emergency level
        $emergencyLevel = BookingPriorityLevel::where('tenant_id', $tenantId)
            ->where('code', 'emergency')
            ->first();

        if (!$emergencyLevel) {
            return;
        }

        // Assign emergency priority
        BookingPriorityAssignment::updateOrCreate(
            ['tenant_id' => $tenantId, 'model_type' => 'App\Models\Booking', 'model_id' => $bookingId],
            ['priority_level_id' => $emergencyLevel->id, 'reason' => $reason, 'assigned_by' => $userId]
        );

        BookingPriorityLog::create([
            'tenant_id' => $tenantId,
            'action' => 'escalated',
            'description' => "Booking #{$bookingId} escalated to Emergency",
            'context' => ['reason' => $reason, 'user_id' => $userId],
        ]);
    }
}
