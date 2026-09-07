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
        Schema::table('serial_user_devices', function (Blueprint $table) {
            if (! Schema::hasColumn('serial_user_devices', 'reseller_id')) {
                $table->foreignId('reseller_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('serial_user_devices', 'expires_at')) {
                $table->dateTime('expires_at')->nullable()->after('status');
                $table->index('expires_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('serial_user_devices', function (Blueprint $table) {
            if (Schema::hasColumn('serial_user_devices', 'reseller_id')) {
                $table->dropConstrainedForeignId('reseller_id');
            }
            if (Schema::hasColumn('serial_user_devices', 'expires_at')) {
                $table->dropIndex(['expires_at']);
                $table->dropColumn('expires_at');
            }
        });
    }
};
