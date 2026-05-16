<?php
namespace App\Events;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
class ReferralCommissionEarned {
    use Dispatchable, SerializesModels;
    public $referral;
    public function __construct($referral) { $this->referral = $referral; }
}
