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
        Schema::table('leads', function (Blueprint $table) {
            if (!Schema::hasColumn('leads', 'assigned_to')) {
                $table->unsignedBigInteger('assigned_to')->nullable()->after('status');
                $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('leads', 'custom_data')) {
                $table->json('custom_data')->nullable()->after('user_agent');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropForeign(['assigned_to']);
            $table->dropColumn('assigned_to');
            $table->dropColumn('custom_data');
        });
    }
};
