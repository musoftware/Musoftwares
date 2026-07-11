<?php

namespace App\Observers;

use App\Models\SerialUserDevice;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;

class TransactionObserver
{
    /**
     * Handle the Transaction "created" event.
     * Automatically reactivate user's serials when they receive or earn money.
     */
    public function created(Transaction $transaction): void
    {
        // Only reactivate serials for received or earned transactions
        if (! in_array($transaction->type, ['received', 'earned'])) {
            return;
        }

        // Skip if transaction has no user
        if (! $transaction->user_id) {
            return;
        }

        try {
            // Find all inactive serials for this user
            $inactiveSerials = SerialUserDevice::query()
                ->where('user_id', $transaction->user_id)
                ->where('status', SerialUserDevice::STATUS_INACTIVE)
                ->get();

            if ($inactiveSerials->isEmpty()) {
                return;
            }

            // Reactivate all inactive serials
            foreach ($inactiveSerials as $serial) {
                $serial->update([
                    'status' => SerialUserDevice::STATUS_ACTIVE,
                    'notes' => ($serial->notes ? $serial->notes.' | ' : '').
                        'Auto-reactivated on '.now()->format('Y-m-d H:i:s').
                        ' due to transaction #'.$transaction->id.
                        ' ('.$transaction->type.': '.$transaction->amount.' '.$transaction->currency.')',
                ]);
            }

            Log::info('Serials auto-reactivated', [
                'user_id' => $transaction->user_id,
                'transaction_id' => $transaction->id,
                'transaction_type' => $transaction->type,
                'serials_count' => $inactiveSerials->count(),
            ]);

        } catch (\Exception $e) {
            // Log error but don't fail the transaction
            Log::error('Failed to auto-reactivate serials', [
                'user_id' => $transaction->user_id,
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
