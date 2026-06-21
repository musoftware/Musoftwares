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
        Schema::create('payment_orders', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('mobile_number', 20);
            $table->decimal('amount', 15, 2);
            $table->string('currency', 10)->default('EGP');
            $table->enum('status', ['pending', 'verified', 'failed', 'expired'])->default('pending');
            $table->timestamp('verified_at')->nullable();
            $table->bigInteger('transaction_id')->unsigned()->nullable();
            $table->foreign('transaction_id')->references('id')->on('sms_payment_gateway_transactions')->onDelete('set null');
            $table->timestamp('expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index('user_id');
            $table->index('mobile_number');
            $table->index('status');
            $table->index('created_at');
            $table->index('expires_at');
            $table->index(['user_id', 'status']);
            $table->index(['mobile_number', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_orders');
    }
};
