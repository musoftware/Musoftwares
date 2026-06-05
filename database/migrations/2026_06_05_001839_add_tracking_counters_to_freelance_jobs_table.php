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
        Schema::table('freelance_jobs', function (Blueprint $table) {
            if (!Schema::hasColumn('freelance_jobs', 'notifications_sent_count')) {
                $table->unsignedInteger('notifications_sent_count')->default(0)->after('status');
            }
            if (!Schema::hasColumn('freelance_jobs', 'views_count')) {
                $table->unsignedInteger('views_count')->default(0)->after('notifications_sent_count');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('freelance_jobs', function (Blueprint $table) {
            $table->dropColumn(['notifications_sent_count', 'views_count']);
        });
    }
};
