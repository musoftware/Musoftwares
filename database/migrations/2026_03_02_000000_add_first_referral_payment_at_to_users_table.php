<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * When a referred user pays for the first time, commission is 10% for the next 1 month; then back to normal (e.g. 1%).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('first_referral_payment_at')->nullable()->after('add_commission_to_total')
                ->comment('When this user (as referred) made their first payment that triggered referral commission');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('first_referral_payment_at');
        });
    }
};
