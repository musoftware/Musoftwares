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
        Schema::create('user_money_transfers', function (Blueprint $table) {
            $table->id();
            
            // User relationships
            $table->bigInteger('sender_id')->unsigned()->index();
            $table->foreign('sender_id')->references('id')->on('users')->onDelete('cascade');
            
            $table->bigInteger('receiver_id')->unsigned()->index();
            $table->foreign('receiver_id')->references('id')->on('users')->onDelete('cascade');
            
            // Transfer details
            $table->decimal('amount', 15, 10); // Large amount with high precision
            $table->bigInteger('currency')->unsigned()->index();
            $table->foreign('currency')->references('id')->on('currencies');
            
            $table->text('reason')->nullable(); // Transfer reason/note
            
            // Status and processing
            $table->enum('status', [
                'pending',
                'processing', 
                'completed',
                'failed',
                'cancelled',
                'rejected'
            ])->default('pending')->index();
            
            // Fee information
            $table->decimal('fee_amount', 15, 10)->default(0);
            $table->bigInteger('fee_currency')->unsigned()->nullable();
            $table->foreign('fee_currency')->references('id')->on('currencies');
            
            // Currency conversion details
            $table->decimal('exchange_rate', 15, 10)->nullable();
            $table->decimal('converted_amount', 15, 10)->nullable();
            $table->bigInteger('converted_currency')->unsigned()->nullable();
            $table->foreign('converted_currency')->references('id')->on('currencies');
            
            // Transaction references for audit trail
            $table->bigInteger('sender_main_transaction_id')->unsigned()->nullable()->index();
            $table->foreign('sender_main_transaction_id')->references('id')->on('transactions')->onDelete('set null');
            
            $table->bigInteger('sender_fee_transaction_id')->unsigned()->nullable()->index();
            $table->foreign('sender_fee_transaction_id')->references('id')->on('transactions')->onDelete('set null');
            
            $table->bigInteger('receiver_main_transaction_id')->unsigned()->nullable()->index();
            $table->foreign('receiver_main_transaction_id')->references('id')->on('transactions')->onDelete('set null');
            
            $table->bigInteger('receiver_fee_transaction_id')->unsigned()->nullable()->index();
            $table->foreign('receiver_fee_transaction_id')->references('id')->on('transactions')->onDelete('set null');
            
            // Admin and processing
            $table->text('admin_notes')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for better performance
            $table->index(['sender_id', 'status']);
            $table->index(['receiver_id', 'status']);
            $table->index(['created_at', 'status']);
            $table->index(['amount', 'currency']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_money_transfers');
    }
};
