<?php

namespace Modules\Booking\app\Features\GroupSessions\Services;

use Modules\Booking\app\Features\GroupSessions\Models\GroupSession;

class WaitlistManager
{
    public function addToWaitlist(int $sessionId, int $customerId)
    {
        $session = GroupSession::findOrFail($sessionId);

        return $session->waitlist()->firstOrCreate([
            'tenant_id' => $session->tenant_id,
            'customer_id' => $customerId,
            'status' => 'waiting'
        ]);
    }

    public function promoteNextAvailable(int $sessionId)
    {
        $session = GroupSession::findOrFail($sessionId);

        $nextInLine = $session->waitlist()
            ->where('status', 'waiting')
            ->orderBy('joined_at', 'asc')
            ->first();

        if ($nextInLine) {
            $nextInLine->update(['status' => 'promoted']);
            return $nextInLine;
        }

        return null;
    }
}
