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
        // Drop if exists so we can re-run after a partial run (e.g. table created then FK failed)
        Schema::dropIfExists('employee_recurring_todo_transactions');

        Schema::create('employee_recurring_todo_transactions', function (Blueprint $table) {
            $table->id();

            // To link back to the recurrent todo setup (short FK name for MySQL 64-char limit)
            $table->unsignedBigInteger('employee_recurring_todo_id');
            $table->foreign('employee_recurring_todo_id', 'er_recur_todo_trans_recur_todo_fk')
                  ->references('id')->on('employee_recurring_todos')->onDelete('cascade');

            // To link to the generated Todo instance
            $table->foreignId('todo_id')->constrained()->onDelete('cascade');

            // For preventing duplication (e.g. employee_recurring_todo_id-2026-02-26)
            $table->string('unique_id')->unique();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_recurring_todo_transactions');
    }
};
