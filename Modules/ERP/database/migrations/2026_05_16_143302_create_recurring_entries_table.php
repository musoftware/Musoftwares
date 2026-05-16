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
        Schema::create('recurring_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->enum('type', ['income', 'expense']);
            $table->string('title');
            $table->text('description')->nullable();

            $table->decimal('amount', 15, 2);
            $table->string('amount_currency', 3);
            $table->decimal('business_amount', 15, 2);
            $table->string('business_currency', 3);
            $table->decimal('exchange_rate', 15, 6);
            $table->date('exchange_rate_date');

            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'yearly']);
            $table->tinyInteger('frequency_day')->nullable();
            $table->tinyInteger('frequency_month')->nullable();

            $table->date('starts_at');
            $table->date('ends_at')->nullable();
            $table->date('next_run_at');
            $table->date('last_run_at')->nullable();

            $table->enum('status', ['active', 'paused', 'cancelled'])->default('active');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_entries');
    }
};
