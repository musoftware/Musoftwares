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
        if (Schema::hasTable('serial_softwares')) {
            Schema::table('serial_softwares', function (Blueprint $table) {
                if (! Schema::hasColumn('serial_softwares', 'trial_enabled')) {
                    $table->boolean('trial_enabled')->default(false)->after('requires_payment');
                }
                if (! Schema::hasColumn('serial_softwares', 'trial_days')) {
                    $table->unsignedInteger('trial_days')->default(1)->after('trial_enabled');
                }
            });
        }

        if (Schema::hasTable('serial_devices')) {
            Schema::table('serial_devices', function (Blueprint $table) {
                if (! Schema::hasColumn('serial_devices', 'trial_claimed_at')) {
                    $table->timestamp('trial_claimed_at')->nullable()->after('status');
                }
            });
        }

        if (Schema::hasTable('serial_user_devices')) {
            Schema::table('serial_user_devices', function (Blueprint $table) {
                if (! Schema::hasColumn('serial_user_devices', 'trial_claimed_at')) {
                    $table->timestamp('trial_claimed_at')->nullable()->after('expires_at');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('serial_softwares')) {
            Schema::table('serial_softwares', function (Blueprint $table) {
                if (Schema::hasColumn('serial_softwares', 'trial_days')) {
                    $table->dropColumn('trial_days');
                }
                if (Schema::hasColumn('serial_softwares', 'trial_enabled')) {
                    $table->dropColumn('trial_enabled');
                }
            });
        }

        if (Schema::hasTable('serial_devices')) {
            Schema::table('serial_devices', function (Blueprint $table) {
                if (Schema::hasColumn('serial_devices', 'trial_claimed_at')) {
                    $table->dropColumn('trial_claimed_at');
                }
            });
        }

        if (Schema::hasTable('serial_user_devices')) {
            Schema::table('serial_user_devices', function (Blueprint $table) {
                if (Schema::hasColumn('serial_user_devices', 'trial_claimed_at')) {
                    $table->dropColumn('trial_claimed_at');
                }
            });
        }
    }
};
