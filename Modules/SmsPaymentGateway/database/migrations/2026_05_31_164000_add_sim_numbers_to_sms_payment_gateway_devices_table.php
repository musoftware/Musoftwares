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
        Schema::table('sms_payment_gateway_devices', function (Blueprint $table) {
            $table->string('sim1_number', 20)->nullable()->after('phone_number');
            $table->string('sim2_number', 20)->nullable()->after('sim1_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sms_payment_gateway_devices', function (Blueprint $table) {
            $table->dropColumn(['sim1_number', 'sim2_number']);
        });
    }
};
