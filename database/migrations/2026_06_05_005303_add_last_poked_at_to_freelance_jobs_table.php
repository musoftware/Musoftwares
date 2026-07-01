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
        // The freelance_jobs table is created by the Freelance module's migration, which may not
        // be registered in every environment (e.g. the in-memory test database). Skip gracefully.
        if (! Schema::hasTable('freelance_jobs')) {
            return;
        }

        if (! Schema::hasColumn('freelance_jobs', 'last_poked_at')) {
            Schema::table('freelance_jobs', function (Blueprint $table) {
                $table->timestamp('last_poked_at')->nullable()->after('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('freelance_jobs')) {
            return;
        }

        if (Schema::hasColumn('freelance_jobs', 'last_poked_at')) {
            Schema::table('freelance_jobs', function (Blueprint $table) {
                $table->dropColumn('last_poked_at');
            });
        }
    }
};
