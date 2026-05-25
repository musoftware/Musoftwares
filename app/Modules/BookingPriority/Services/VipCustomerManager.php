<?php

namespace App\Modules\BookingPriority\Services;

use App\Modules\BookingPriority\Models\BookingPriorityAssignment;

class VipCustomerManager
{
    public function assignVipStatus(int $tenantId, int $customerId, int $priorityLevelId, string $reason = ''): void
    {
        BookingPriorityAssignment::updateOrCreate(
            ['tenant_id' => $tenantId, 'model_type' => 'App\Models\Customer', 'model_id' => $customerId],
            ['priority_level_id' => $priorityLevelId, 'reason' => $reason]
        );
    }

    public function isVip(int $tenantId, int $customerId): bool
    {
        return BookingPriorityAssignment::where('tenant_id', $tenantId)
            ->where('model_type', 'App\Models\Customer')
            ->where('model_id', $customerId)
            ->exists();
    }
}
