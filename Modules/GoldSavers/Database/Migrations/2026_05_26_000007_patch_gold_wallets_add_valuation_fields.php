<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gold_wallets', function (Blueprint $table) {
            if (!Schema::hasColumn('gold_wallets', 'current_value')) {
                $table->decimal('current_value', 14, 4)->default(0)->after('balance_amount');
            }
            if (!Schema::hasColumn('gold_wallets', 'unrealized_pnl')) {
                $table->decimal('unrealized_pnl', 14, 4)->default(0)->after('current_value');
            }
            if (!Schema::hasColumn('gold_wallets', 'pnl_pct')) {
                $table->decimal('pnl_pct', 8, 4)->default(0)->after('unrealized_pnl');
            }
            if (!Schema::hasColumn('gold_wallets', 'last_valuation_at')) {
                $table->timestamp('last_valuation_at')->nullable()->after('pnl_pct');
            }
        });
    }

    public function down(): void
    {
        Schema::table('gold_wallets', function (Blueprint $table) {
            $table->dropColumn(['current_value', 'unrealized_pnl', 'pnl_pct', 'last_valuation_at']);
        });
    }
};
