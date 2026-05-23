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
        Schema::create('employee_recurring_todos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // The employee
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('priority')->default('medium');
            
            // Recurring setup
            $table->string('recurring'); // 'day', 'week', 'month', 'year'
            $table->integer('recurring_times')->default(1);
            $table->string('recurring_times_week')->nullable();
            $table->string('recurring_times_month')->nullable();
            $table->string('recurring_times_year')->nullable();
            $table->date('current_date'); // Starting date
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_recurring_todos');
    }
};
