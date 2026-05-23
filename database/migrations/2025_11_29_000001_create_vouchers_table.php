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
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            
            // Voucher details
            $table->string('name');
            $table->text('description')->nullable();
            
            // Spend X get Y rules
            $table->decimal('spend_amount', 15, 10); // Amount user needs to spend
            $table->bigInteger('spend_currency')->unsigned()->index();
            $table->foreign('spend_currency')->references('id')->on('currencies');
            
            $table->decimal('reward_amount', 15, 10); // Amount user gets as reward
            $table->bigInteger('reward_currency')->unsigned()->index();
            $table->foreign('reward_currency')->references('id')->on('currencies');
            
            // Voucher settings
            $table->enum('type', ['fixed', 'percentage'])->default('fixed'); // Fixed amount or percentage
            $table->decimal('reward_percentage', 5, 2)->nullable(); // If percentage type
            
            // Usage limits
            $table->integer('max_uses_per_user')->nullable(); // Max times a user can use this voucher
            $table->integer('max_total_uses')->nullable(); // Max total uses across all users
            $table->integer('current_uses')->default(0); // Current usage count
            
            // Validity period
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            
            // Admin notes
            $table->text('admin_notes')->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['is_active', 'expires_at']);
            $table->index(['spend_currency', 'reward_currency']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};

