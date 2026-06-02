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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'account_status')) {
                $table->string('account_status', 20)->default('active')->after('deleted_at')->index();
            }
            if (!Schema::hasColumn('users', 'block_reason')) {
                $table->text('block_reason')->nullable()->after('account_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'account_status')) {
                $table->dropColumn('account_status');
            }
            if (Schema::hasColumn('users', 'block_reason')) {
                $table->dropColumn('block_reason');
            }
        });
    }
};
