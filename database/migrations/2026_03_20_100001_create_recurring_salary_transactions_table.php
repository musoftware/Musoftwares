<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_salary_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('recurring_salary_id');
            $table->foreign('recurring_salary_id')->references('id')->on('recurring_salaries')->cascadeOnDelete();
            $table->unsignedBigInteger('transaction_id');
            $table->foreign('transaction_id')->references('id')->on('transactions')->cascadeOnDelete();
            $table->string('unique_id', 255)->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_salary_transactions');
    }
};
