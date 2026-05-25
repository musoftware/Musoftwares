<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if the table exists
        if (Schema::hasTable('sms_payment_gateway_devices')) {
            if (config('database.default') !== 'sqlite') {
                // Drop the unique constraint first if it exists
                try {
                    Schema::table('sms_payment_gateway_devices', function (Blueprint $table) {
                        $table->dropUnique(['device_token']);
                    });
                } catch (\Exception $e) {
                    // Constraint might not exist, continue
                }
                
                // Make device_token nullable using raw SQL for better compatibility
                DB::statement('ALTER TABLE `sms_payment_gateway_devices` MODIFY `device_token` VARCHAR(255) NULL');
                
                // Re-add unique constraint (nullable unique allows multiple NULLs)
                Schema::table('sms_payment_gateway_devices', function (Blueprint $table) {
                    $table->unique('device_token');
                });
            } else {
                // For SQLite, just drop and recreate the table or skip the modification
                // SQLite doesn't support MODIFY, so we'll just skip this change for testing
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('sms_payment_gateway_devices')) {
            if (config('database.default') !== 'sqlite') {
                // Drop the unique constraint
                try {
                    Schema::table('sms_payment_gateway_devices', function (Blueprint $table) {
                        $table->dropUnique(['device_token']);
                    });
                } catch (\Exception $e) {
                    // Constraint might not exist, continue
                }
                
                // Make device_token not nullable again
                DB::statement('ALTER TABLE `sms_payment_gateway_devices` MODIFY `device_token` VARCHAR(255) NOT NULL');
                
                // Re-add unique constraint
                Schema::table('sms_payment_gateway_devices', function (Blueprint $table) {
                    $table->unique('device_token');
                });
            } else {
                // For SQLite, skip the reversal
            }
        }
    }
};
