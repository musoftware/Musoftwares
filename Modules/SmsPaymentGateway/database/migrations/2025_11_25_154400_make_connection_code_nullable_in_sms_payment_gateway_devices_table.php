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
                        $table->dropUnique(['connection_code']);
                    });
                } catch (\Exception $e) {
                    // Constraint might not exist, continue
                }
                
                // Make connection_code nullable using raw SQL for better compatibility
                DB::statement('ALTER TABLE `sms_payment_gateway_devices` MODIFY `connection_code` VARCHAR(64) NULL');
                
                // Re-add unique constraint (nullable unique allows multiple NULLs)
                Schema::table('sms_payment_gateway_devices', function (Blueprint $table) {
                    $table->unique('connection_code');
                });
            } else {
                // For SQLite, skip the modification
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
                        $table->dropUnique(['connection_code']);
                    });
                } catch (\Exception $e) {
                    // Constraint might not exist, continue
                }
                
                // Make connection_code not nullable again
                DB::statement('ALTER TABLE `sms_payment_gateway_devices` MODIFY `connection_code` VARCHAR(64) NOT NULL');
                
                // Re-add unique constraint
                Schema::table('sms_payment_gateway_devices', function (Blueprint $table) {
                    $table->unique('connection_code');
                });
            } else {
                // For SQLite, skip the reversal
            }
        }
    }
};
