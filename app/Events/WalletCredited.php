<?php
namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WalletCredited {
    use Dispatchable, SerializesModels;

    public $transaction;
    public $amount;
    public $currencyId;

    public function __construct($transaction, $amount = null, $currencyId = null) { 
        $this->transaction = $transaction; 
        $this->amount = $amount ?? ($transaction->amount ?? null);
        $this->currencyId = $currencyId ?? ($transaction->currency_id ?? null);

        if (!$this->currencyId) {
            throw new \Exception("WalletCredited event is missing currency_id.");
        }
    }
}
