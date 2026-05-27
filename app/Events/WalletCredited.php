<?php
namespace App\Events;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
class WalletCredited {
    use Dispatchable, SerializesModels;
    public $transaction;
    public $amount;
    public $currency;
    public function __construct($transaction, $amount = null, $currency = null) { 
        $this->transaction = $transaction; 
        $this->amount = $amount ?? ($transaction->amount ?? null);
        $this->currency = $currency ?? ($transaction->currency?->currency ?? 'USD');
    }
}
