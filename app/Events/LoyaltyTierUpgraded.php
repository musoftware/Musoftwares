<?php

namespace App\Events;

use App\Models\LoyaltyTier;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LoyaltyTierUpgraded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly LoyaltyTier $newTier,
        public readonly ?LoyaltyTier $previousTier,
    ) {}
}
