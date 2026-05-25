<?php

namespace Modules\Booking\app\Features\GroupSessions\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Booking\app\Features\GroupSessions\Services\WaitlistManager;
use Modules\Booking\app\Features\GroupSessions\Services\GroupCapacityManager;
use Modules\Booking\app\Features\GroupSessions\Events\WaitlistPromoted;

class PromoteWaitlistJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $sessionId;

    public function __construct(int $sessionId)
    {
        $this->sessionId = $sessionId;
    }

    public function handle(WaitlistManager $waitlistManager, GroupCapacityManager $capacityManager): void
    {
        $promotedEntry = $waitlistManager->promoteNextAvailable($this->sessionId);

        if ($promotedEntry) {
            // Secure their seat
            $capacityManager->secureSeat($this->sessionId, $promotedEntry->customer_id);

            // Fire event to send notifications
            event(new WaitlistPromoted($promotedEntry));
        }
    }
}
