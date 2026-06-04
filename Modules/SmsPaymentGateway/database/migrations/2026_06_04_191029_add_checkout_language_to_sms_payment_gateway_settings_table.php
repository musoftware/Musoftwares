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
            if (!Schema::hasColumn('sms_payment_gateway_settings', 'checkout_language')) {
                $table->string('checkout_language', 10)->default('ar')->after('hide_method_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sms_payment_gateway_settings', function (Blueprint $table) {
            $table->dropColumn('checkout_language');
        });
    }
};
