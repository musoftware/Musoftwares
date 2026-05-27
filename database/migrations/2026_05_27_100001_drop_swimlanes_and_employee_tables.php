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
        Schema::table('tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tasks', 'swimlane_id')) {
                $table->dropForeign(['swimlane_id']);
                $table->dropColumn('swimlane_id');
            }
        });

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
