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
        // Add test_mode to users table
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'sms_payment_gateway_test_mode')) {
                $table->boolean('sms_payment_gateway_test_mode')->default(false);
            }
        });

        // Add test_mode to transactions table
        Schema::table('sms_payment_gateway_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('sms_payment_gateway_transactions', 'is_test')) {
                $table->boolean('is_test')->default(false);
                $table->index('is_test');
            }
        });

        // Add test_mode to orders table
        Schema::table('payment_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('payment_orders', 'is_test')) {
                $table->boolean('is_test')->default(false);
                $table->index('is_test');
            }
        });

        // Add test_mode to webhooks table (for test webhook events)
        Schema::table('sms_payment_gateway_webhooks', function (Blueprint $table) {
            if (!Schema::hasColumn('sms_payment_gateway_webhooks', 'test_events_sent')) {
                $table->integer('test_events_sent')->default(0);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('sms_payment_gateway_test_mode');
        });

        Schema::table('sms_payment_gateway_transactions', function (Blueprint $table) {
            $table->dropIndex(['is_test']);
            $table->dropColumn('is_test');
        });

        Schema::table('payment_orders', function (Blueprint $table) {
            $table->dropIndex(['is_test']);
            $table->dropColumn('is_test');
        });

        Schema::table('sms_payment_gateway_webhooks', function (Blueprint $table) {
            $table->dropColumn('test_events_sent');
        });
    }
};
