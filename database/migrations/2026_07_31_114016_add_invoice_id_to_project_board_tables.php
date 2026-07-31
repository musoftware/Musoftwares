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
        // Add invoice_id to project_board_items
        Schema::table('project_board_items', function (Blueprint $table) {
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->cascadeOnDelete();
        });

        // Add invoice_id and make project_id nullable in project_board_notes
        Schema::table('project_board_notes', function (Blueprint $table) {
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->change();
        });

        // Add invoice_id to tasks
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->cascadeOnDelete();
        });

        // Add invoice_id to todos
        Schema::table('todos', function (Blueprint $table) {
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
            $table->dropColumn('invoice_id');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
            $table->dropColumn('invoice_id');
        });

        Schema::table('project_board_notes', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
            $table->dropColumn('invoice_id');
            $table->foreignId('project_id')->nullable(false)->change();
        });

        Schema::table('project_board_items', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
            $table->dropColumn('invoice_id');
        });
    }
};
