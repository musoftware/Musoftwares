<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Invoice;
use App\Models\Transaction;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ultra-fast single SQL update to sync transaction currency from invoice
        try {
            \Illuminate\Support\Facades\DB::statement("
                UPDATE transactions t
                INNER JOIN invoices i ON t.reason LIKE CONCAT('Invoice #', i.id, '%')
                SET t.currency_id = i.currency_id
                WHERE t.currency_id != i.currency_id AND i.currency_id IS NOT NULL
            ");
        } catch (\Throwable $e) {
            // Fallback for drivers that do not support multi-table UPDATE
            $invoices = Invoice::whereNotNull('currency_id')->get();
            foreach ($invoices as $inv) {
                Transaction::where('reason', 'like', "Invoice #{$inv->id}%")
                    ->where('currency_id', '!=', $inv->currency_id)
                    ->update(['currency_id' => $inv->currency_id]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse action needed
    }
};
