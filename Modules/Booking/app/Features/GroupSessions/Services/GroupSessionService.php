<?php

namespace Modules\Booking\app\Features\GroupSessions\Services;

use Modules\Booking\app\Features\GroupSessions\Models\GroupSession;
use Modules\Booking\app\Features\GroupSessions\Events\GroupSessionFull;

class GroupSessionService
{
    protected $capacityManager;
    protected $waitlistManager;

    public function __construct(GroupCapacityManager $capacityManager, WaitlistManager $waitlistManager)
    {
        $this->capacityManager = $capacityManager;
        $this->waitlistManager = $waitlistManager;
    }

    public function joinSession(int $sessionId, int $customerId)
    {
        $result = $this->capacityManager->secureSeat($sessionId, $customerId);

        if (!$result['success'] && $result['reason'] === 'full') {
            event(new GroupSessionFull(GroupSession::find($sessionId)));
            
            $waitlistEntry = $this->waitlistManager->addToWaitlist($sessionId, $customerId);
            return ['status' => 'waitlisted', 'entry' => $waitlistEntry];
        }

        return ['status' => 'confirmed'];
    }
}
