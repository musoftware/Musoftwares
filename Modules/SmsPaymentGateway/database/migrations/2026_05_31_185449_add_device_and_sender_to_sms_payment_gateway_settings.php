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
            $table->foreignId('vodafone_cash_device_id')->nullable()->constrained('sms_payment_gateway_devices')->nullOnDelete();
            $table->string('vodafone_cash_allowed_sender')->nullable();
            
            $table->foreignId('instapay_device_id')->nullable()->constrained('sms_payment_gateway_devices')->nullOnDelete();
            $table->string('instapay_allowed_sender')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sms_payment_gateway_settings', function (Blueprint $table) {
            $table->dropForeign(['vodafone_cash_device_id']);
            $table->dropColumn('vodafone_cash_device_id');
            $table->dropColumn('vodafone_cash_allowed_sender');
            
            $table->dropForeign(['instapay_device_id']);
            $table->dropColumn('instapay_device_id');
            $table->dropColumn('instapay_allowed_sender');
        });
    }
};
