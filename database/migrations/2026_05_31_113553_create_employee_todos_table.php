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
        Schema::create('employee_todos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('priority', ['high', 'medium', 'low'])->default('medium');
            $table->enum('recurring', ['day', 'week', 'month', 'year'])->default('day');
            $table->integer('recurring_times')->default(1);
            $table->string('recurring_times_week')->nullable();
            $table->string('recurring_times_month')->nullable();
            $table->string('recurring_times_year')->nullable();
            $table->date('current_date');
            $table->integer('transactions_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_todos');
    }
};
