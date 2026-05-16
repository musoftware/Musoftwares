<?php
namespace App\Events;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
class WithdrawalApproved {
    use Dispatchable, SerializesModels;
    public $withdrawal;
    public function __construct($withdrawal) { $this->withdrawal = $withdrawal; }
}
