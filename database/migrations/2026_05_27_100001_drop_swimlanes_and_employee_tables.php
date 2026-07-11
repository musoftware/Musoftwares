<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            DB::statement('ALTER TABLE tasks DROP FOREIGN KEY tasks_swimlane_id_foreign');
        } catch (Exception $e) {
        }
        try {
            DB::statement('ALTER TABLE tasks DROP FOREIGN KEY tasks_swinlane_id_foreign');
        } catch (Exception $e) {
        }

        if (Schema::hasColumn('tasks', 'swimlane_id')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->dropColumn('swimlane_id');
            });
        }

        Schema::dropIfExists('todo_swimlanes');
        Schema::dropIfExists('employee_attendances');
        Schema::dropIfExists('employee_recurring_todo_transactions');
        Schema::dropIfExists('employee_recurring_todos');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
