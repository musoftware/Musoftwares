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
        Schema::table('project_board_items', function (Blueprint $table) {
            $table->time('appearance_time')->nullable()->after('lane');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_board_items', function (Blueprint $table) {
            $table->dropColumn('appearance_time');
        });
    }
};
