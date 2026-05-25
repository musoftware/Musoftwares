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
        Schema::table('sms_payment_gateway_transactions', function (Blueprint $table) {
            // Add new columns
            $table->string('phone_number', 20)->nullable()->after('sender');
            $table->string('sender_name', 255)->nullable()->after('phone_number');
            
            // Remove reference column
            $table->dropColumn('reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sms_payment_gateway_transactions', function (Blueprint $table) {
            // Add back reference column
            $table->string('reference')->nullable()->after('sender');
            
            // Remove new columns
            $table->dropColumn(['phone_number', 'sender_name']);
        });
    }
};
