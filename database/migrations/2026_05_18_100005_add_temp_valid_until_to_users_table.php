<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds temp_valid_until to users table.
 * Used by SerialUserDeviceController::updateUserTempValid().
 * Allows admin to grant temporary license access to a user
 * without changing their device assignment statuses.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'temp_valid_until')) {
                $table->timestamp('temp_valid_until')->nullable()->after('last_activity_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('temp_valid_until');
        });
    }
};
