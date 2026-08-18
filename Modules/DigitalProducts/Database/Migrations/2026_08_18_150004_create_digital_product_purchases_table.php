<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('digital_product_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('digital_product_id')->constrained('digital_products')->cascadeOnDelete();
            $table->decimal('amount_paid', 10, 2);
            $table->unsignedBigInteger('currency_id')->default(1);
            $table->string('payment_method', 50)->default('wallet');
            $table->string('transaction_id')->nullable();
            $table->string('status', 30)->default('completed');
            $table->timestamps();

            $table->unique(['user_id', 'digital_product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('digital_product_purchases');
    }
};
