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
        // 1. Restore all client transactions to match the client's currency in the database
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

        // 2. Drop legacy duplicate 'currency' column from transactions if it exists in production
        // It shadows the Eloquent currency() relationship and stores outdated default 1 (USD)
        if (\Illuminate\Support\Facades\Schema::hasColumn('transactions', 'currency')) {
            \Illuminate\Support\Facades\Schema::table('transactions', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->dropColumn('currency');
            });
        }

        if (\Illuminate\Support\Facades\Schema::hasColumn('cost_transactions', 'currency')) {
            \Illuminate\Support\Facades\Schema::table('cost_transactions', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->dropColumn('currency');
            });
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
