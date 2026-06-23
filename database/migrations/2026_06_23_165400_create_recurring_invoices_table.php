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
        Schema::create('recurring_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->string('title');
            $table->decimal('amount', 15, 2)->default(0);
            
            $table->date('start_date');
            $table->date('current_date')->nullable();
            
            $table->enum('recurring', ['day', 'week', 'month', 'year'])->default('month');
            $table->integer('recurring_times')->default(1);
            $table->string('recurring_times_week')->nullable();
            $table->string('recurring_times_month')->nullable();
            $table->string('recurring_times_year')->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_invoices');
    }
};
