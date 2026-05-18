<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add admin-managed operational fields to users table.
 * Recovered from old musoftwares.com project.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Account lifecycle
            if (!Schema::hasColumn('users', 'account_status')) {
                $table->string('account_status')->default('active')->after('role');
            }
            if (!Schema::hasColumn('users', 'block_reason')) {
                $table->text('block_reason')->nullable()->after('account_status');
            }

            // KYC notes (provider/reference already added in kyc migration)
            if (!Schema::hasColumn('users', 'kyc_notes')) {
                $table->text('kyc_notes')->nullable();
            }

            // Last activity tracking
            if (!Schema::hasColumn('users', 'last_activity_at')) {
                $table->timestamp('last_activity_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumnIfExists('account_status');
            $table->dropColumnIfExists('block_reason');
            $table->dropColumnIfExists('kyc_notes');
            $table->dropColumnIfExists('last_activity_at');
        });
    }
};
