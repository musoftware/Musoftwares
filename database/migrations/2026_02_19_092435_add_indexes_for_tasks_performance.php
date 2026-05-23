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
        Schema::table('todos', function (Blueprint $table) {
            $table->index(['task_id', 'completed'], 'idx_todos_task_completed');
        });

        Schema::table('tasks_share', function (Blueprint $table) {
            $table->index(['task_id', 'user_id'], 'idx_tasks_share_task_user');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->index(['user_id', 'archived'], 'idx_tasks_user_archived');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            $table->dropIndex('idx_todos_task_completed');
        });

        Schema::table('tasks_share', function (Blueprint $table) {
            $table->dropIndex('idx_tasks_share_task_user');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('idx_tasks_user_archived');
        });
    }
};
