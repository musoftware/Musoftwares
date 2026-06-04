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
            if (!Schema::hasColumn('sms_payment_gateway_settings', 'brand_name')) {
                $table->string('brand_name')->nullable()->after('wallet_phone_number');
            }
            if (!Schema::hasColumn('sms_payment_gateway_settings', 'hide_method_name')) {
                $table->boolean('hide_method_name')->default(false)->after('brand_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sms_payment_gateway_settings', function (Blueprint $table) {
            $table->dropColumn(['brand_name', 'hide_method_name']);
        });
    }
};
