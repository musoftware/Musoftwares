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
        // Client ledger transactions must ALWAYS reflect the client's currency (users.currency_id)
        try {
            \Illuminate\Support\Facades\DB::statement("
                UPDATE transactions t
                INNER JOIN users u ON t.user_id = u.id
                SET t.currency_id = u.currency_id
                WHERE u.currency_id IS NOT NULL AND t.currency_id != u.currency_id
            ");
        } catch (\Throwable $e) {
            // Log or ignore
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
