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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            
            // Coupon code
            $table->string('code')->unique()->index();
            
            // Coupon details
            $table->string('name');
            $table->text('description')->nullable();
            
            // Discount/Reward type
            $table->enum('type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('discount_amount', 15, 10)->nullable(); // Fixed discount amount
            $table->decimal('discount_percentage', 5, 2)->nullable(); // Percentage discount
            $table->bigInteger('currency')->unsigned()->index();
            $table->foreign('currency')->references('id')->on('currencies');
            
            // Minimum purchase requirement
            $table->decimal('min_purchase_amount', 15, 10)->nullable();
            
            // Usage limits
            $table->integer('max_uses_per_user')->nullable(); // Max times a user can use this coupon
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
            $table->index(['code', 'is_active']);
            $table->index(['is_active', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};

