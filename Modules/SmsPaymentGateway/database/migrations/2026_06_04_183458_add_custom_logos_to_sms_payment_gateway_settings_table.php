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
            $table->json('custom_logos')->nullable()->after('whitelist_senders');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sms_payment_gateway_settings', function (Blueprint $table) {
            $table->dropColumn('custom_logos');
        });
    }
};
