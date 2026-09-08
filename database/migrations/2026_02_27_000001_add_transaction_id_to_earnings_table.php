<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Earnings with transaction_id null are "uncleared"; when converted to balance,
     * a transaction is created and earning.transaction_id is set.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('earnings', 'transaction_id')) {
            Schema::table('earnings', function (Blueprint $table) {
                $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('earnings', 'transaction_id')) {
            return;
        }
        Schema::table('earnings', function (Blueprint $table) {
            $table->dropForeign(['transaction_id']);
            $table->dropColumn('transaction_id');
        });
    }
};
