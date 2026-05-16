<?php
namespace App\Events;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
class WalletCredited {
    use Dispatchable, SerializesModels;
    public $transaction;
    public function __construct($transaction) { $this->transaction = $transaction; }
}
