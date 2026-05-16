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
        Schema::create('recurring_execution_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recurring_entry_id')->constrained('recurring_entries')->cascadeOnDelete();
            $table->timestamp('executed_at');

            $table->decimal('amount', 15, 2);
            $table->string('amount_currency', 3);
            $table->decimal('business_amount', 15, 2);
            $table->string('business_currency', 3);
            $table->decimal('exchange_rate', 15, 6);
            $table->date('exchange_rate_date');

            $table->enum('status', ['success', 'failed']);
            $table->text('note')->nullable();

            // Immutable
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_execution_logs');
    }
};
