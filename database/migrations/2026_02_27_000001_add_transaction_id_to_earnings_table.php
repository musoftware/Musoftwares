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
        if (Schema::hasColumn('earnings', 'transaction_id')) {
            Schema::table('earnings', function (Blueprint $table) {
                $foreignExists = collect(\DB::select("SHOW CREATE TABLE earnings"))->first();
                if ($foreignExists && strpos($foreignExists->{'Create Table'} ?? '', 'transaction_id') !== false) {
                    $indexExists = collect(\DB::select("SHOW INDEX FROM earnings WHERE Column_name = 'transaction_id'"))->isNotEmpty();
                    if (!$indexExists || strpos($foreignExists->{'Create Table'} ?? '', 'transactions') === false) {
                        $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('set null');
                    }
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('earnings', 'transaction_id')) {
            return;
        }
        Schema::table('earnings', function (Blueprint $table) {
            $table->dropForeign(['transaction_id']);
            $table->dropColumn('transaction_id');
        });
    }
};
