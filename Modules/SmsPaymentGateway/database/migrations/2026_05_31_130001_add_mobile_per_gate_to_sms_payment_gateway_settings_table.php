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
        Schema::table('sms_payment_gateway_settings', function (Blueprint $table) {
            $table->string('instapay_phone_number', 20)->nullable()->after('wallet_phone_number');
            $table->string('vodafone_cash_phone_number', 20)->nullable()->after('instapay_phone_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sms_payment_gateway_settings', function (Blueprint $table) {
            $table->dropColumn(['instapay_phone_number', 'vodafone_cash_phone_number']);
        });
    }
};
