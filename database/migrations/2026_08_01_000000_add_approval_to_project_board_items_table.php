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
            $table->string('client_approval_status', 30)->default('pending'); // pending, approved, revision_requested
            $table->text('client_feedback')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_board_items', function (Blueprint $table) {
            $table->dropColumn(['client_approval_status', 'client_feedback']);
        });
    }
};
