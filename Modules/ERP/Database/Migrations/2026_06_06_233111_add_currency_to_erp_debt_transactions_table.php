<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('erp_debt_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('erp_debt_transactions', 'currency_id')) {
                $table->foreignId('currency_id')->nullable()->after('amount')->constrained('currencies')->nullOnDelete();
            }
            if (!Schema::hasColumn('erp_debt_transactions', 'business_amount')) {
                $table->decimal('business_amount', 15, 2)->nullable()->after('currency_id');
            }
            if (!Schema::hasColumn('erp_debt_transactions', 'business_currency_id')) {
                $table->foreignId('business_currency_id')->nullable()->after('business_amount')->constrained('currencies')->nullOnDelete();
            }
            if (!Schema::hasColumn('erp_debt_transactions', 'exchange_rate')) {
                $table->decimal('exchange_rate', 15, 6)->nullable()->after('business_currency_id');
            }
            if (!Schema::hasColumn('erp_debt_transactions', 'exchange_rate_date')) {
                $table->date('exchange_rate_date')->nullable()->after('exchange_rate');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erp_debt_transactions', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            $table->dropForeign(['business_currency_id']);
            $table->dropColumn(['currency_id', 'business_amount', 'business_currency_id', 'exchange_rate', 'exchange_rate_date']);
        });
    }
};
