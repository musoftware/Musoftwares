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
        if (Schema::hasTable('auto_sms_devices')) {
            if (config('database.default') !== 'sqlite') {
                // Drop the unique constraint first if it exists
                try {
                    Schema::table('auto_sms_devices', function (Blueprint $table) {
                        $table->dropUnique(['connection_code']);
                    });
                } catch (\Exception $e) {
                    // Constraint might not exist, continue
                }
                
                // Make connection_code nullable using raw SQL for better compatibility
                DB::statement('ALTER TABLE `auto_sms_devices` MODIFY `connection_code` VARCHAR(64) NULL');
                
                // Re-add unique constraint (nullable unique allows multiple NULLs)
                Schema::table('auto_sms_devices', function (Blueprint $table) {
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
        if (Schema::hasTable('auto_sms_devices')) {
            if (config('database.default') !== 'sqlite') {
                // Drop the unique constraint
                try {
                    Schema::table('auto_sms_devices', function (Blueprint $table) {
                        $table->dropUnique(['connection_code']);
                    });
                } catch (\Exception $e) {
                    // Constraint might not exist, continue
                }
                
                // Make connection_code not nullable again
                DB::statement('ALTER TABLE `auto_sms_devices` MODIFY `connection_code` VARCHAR(64) NOT NULL');
                
                // Re-add unique constraint
                Schema::table('auto_sms_devices', function (Blueprint $table) {
                    $table->unique('connection_code');
                });
            } else {
                // For SQLite, skip the reversal
            }
        }
    }
};
