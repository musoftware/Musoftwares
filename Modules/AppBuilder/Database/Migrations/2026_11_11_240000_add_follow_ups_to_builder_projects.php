<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** When a quiet, unpaid chat got its reminder email (after two days) and its discount email (after a week). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('builder_projects', function (Blueprint $table) {
            $table->timestamp('follow_up_at')->nullable()->after('leave_reminder_at');
            $table->timestamp('follow_up_offer_at')->nullable()->after('follow_up_at');
        });
    }

    public function down(): void
    {
        Schema::table('builder_projects', function (Blueprint $table) {
            $table->dropColumn(['follow_up_at', 'follow_up_offer_at']);
        });
    }
};
