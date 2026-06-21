<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_salaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->string('title');
            $table->double('amount', 30, 3);
            $table->unsignedBigInteger('currency')->default(1);
            $table->foreign('currency')->references('id')->on('currencies');
            $table->string('reason')->nullable();
            $table->enum('recurring', ['day', 'week', 'month', 'year']);
            $table->integer('recurring_times')->default(1);
            $table->text('recurring_times_week')->nullable();
            $table->text('recurring_times_month')->nullable();
            $table->text('recurring_times_year')->nullable();
            $table->date('start_date')->nullable();
            $table->date('current_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_salaries');
    }
};
