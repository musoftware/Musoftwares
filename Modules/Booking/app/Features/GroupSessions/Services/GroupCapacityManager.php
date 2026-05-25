<?php

namespace Modules\Booking\app\Features\GroupSessions\Services;

use Modules\Booking\app\Features\GroupSessions\Models\GroupSession;
use Illuminate\Support\Facades\DB;

class GroupCapacityManager
{
    /**
     * Attempts to secure a seat. Uses pessimistic locking.
     */
    public function secureSeat(int $sessionId, int $customerId)
    {
        return DB::transaction(function () use ($sessionId, $customerId) {
            $session = GroupSession::lockForUpdate()->findOrFail($sessionId);

            $currentParticipants = $session->participants()->where('status', 'confirmed')->count();

            if ($currentParticipants >= $session->max_capacity) {
                return ['success' => false, 'reason' => 'full'];
            }

            $session->participants()->firstOrCreate([
                'tenant_id' => $session->tenant_id,
                'customer_id' => $customerId,
            ]);

            return ['success' => true];
        });
    }
}
