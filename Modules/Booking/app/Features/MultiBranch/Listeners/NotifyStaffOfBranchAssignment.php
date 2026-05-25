<?php

namespace Modules\Booking\app\Features\MultiBranch\Listeners;

use Modules\Booking\app\Features\MultiBranch\Events\BranchStaffAssigned;
use Modules\Booking\app\Features\MultiBranch\Notifications\YouWereAssignedToBranch;

class NotifyStaffOfBranchAssignment
{
    /**
     * Handle the event.
     */
    public function handle(BranchStaffAssigned $event): void
    {
        // Send the notification to the user
        $event->user->notify(new YouWereAssignedToBranch($event->branch, $event->role));
    }
}
