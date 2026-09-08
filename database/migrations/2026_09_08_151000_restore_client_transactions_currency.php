<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Restore all client transactions to match the client's currency in the database
        try {
            DB::statement("
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
