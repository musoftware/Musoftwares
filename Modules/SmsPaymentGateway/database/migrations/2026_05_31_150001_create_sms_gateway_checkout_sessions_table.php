<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_gateway_checkout_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 40)->unique(); // cs_xxxx (public facing ID)
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('api_key_id')->nullable();
            $table->decimal('amount', 12, 2);
            $table->unsignedBigInteger('currency_id');
            $table->enum('status', ['open', 'complete', 'expired'])->default('open');
            $table->string('success_url', 1000);
            $table->string('cancel_url', 1000)->nullable();
            $table->string('webhook_url', 1000)->nullable(); // override per-session
            $table->string('customer_name', 255)->nullable();
            $table->string('customer_email', 255)->nullable();
            $table->string('customer_phone', 30)->nullable();
            $table->json('metadata')->nullable(); // merchant custom data
            $table->json('payment_method_types')->nullable(); // ['vodafone_cash', 'instapay']
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->string('transaction_reference', 255)->nullable(); // reference entered by customer
            $table->boolean('is_test')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['session_id', 'status']);
            $table->index('expires_at');

            $table->foreign('currency_id')->references('id')->on('currencies');
            $table->foreign('api_key_id')->references('id')->on('sms_gateway_api_keys')->nullOnDelete();
            $table->foreign('transaction_id')->references('id')->on('sms_payment_gateway_transactions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_gateway_checkout_sessions');
    }
};
