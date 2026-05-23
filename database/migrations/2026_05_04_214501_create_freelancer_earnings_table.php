<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Freelancer Earnings — created automatically when a task is approved.
     * Tracks payment status from pending → paid.
     * Separate from the existing `earnings` table which is for referral commissions.
     */
    public function up(): void
    {
        Schema::create('freelancer_earnings', function (Blueprint $table) {
            $table->id();

            // The freelancer who earned this payment
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            // The approved task that triggered this earning
            $table->foreignId('freelancer_task_id')
                ->constrained('freelancer_tasks')
                ->onDelete('cascade');

            // Payment amount
            $table->decimal('amount', 10, 2)->default(0);

            // Currency for the payment
            $table->bigInteger('currency_id')->unsigned()->nullable();
            $table->foreign('currency_id')->references('id')->on('currencies')->onDelete('set null');

            // Payment status
            $table->enum('status', ['pending', 'paid'])->default('pending')->index();

            // When payment was processed
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();

            // Performance indexes
            $table->index(['user_id', 'status']);
            $table->unique('freelancer_task_id'); // One earning per task
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('freelancer_earnings');
    }
};
