<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('musoftware_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('musoftware_clients');
            $table->string('external_order_id');
            $table->string('internal_order_id')->unique();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3);
            $table->text('description')->nullable();
            $table->text('success_url');
            $table->text('failure_url')->nullable();
            $table->text('webhook_url')->nullable();
            $table->json('customer_data')->nullable();
            $table->json('metadata')->nullable();
            $table->enum('status', ['pending', 'success', 'failed', 'cancelled'])->default('pending');
            $table->text('kashier_payment_url')->nullable();
            $table->string('kashier_transaction_id')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('musoftware_payments');
    }
};
