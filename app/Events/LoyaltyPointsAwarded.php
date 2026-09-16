<?php

namespace App\Events;

use App\Models\LoyaltyPointTransaction;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LoyaltyPointsAwarded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly LoyaltyPointTransaction $transaction,
    ) {}
}
