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
        Schema::create('gold_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wallet_id')->index();
            $table->enum('type', ['buy', 'sell', 'transfer_in', 'transfer_out']);
            
            $table->decimal('grams', 10, 2);
            $table->integer('karat')->default(21); // 24, 21, 18
            $table->decimal('price_per_gram', 10, 2);
            $table->decimal('total_amount', 12, 2); // grams * price_per_gram + fees
            $table->decimal('fees', 10, 2)->default(0); // workmanship/stamp fees
            
            $table->foreignId('currency_id')->default(1)->constrained('currencies')->onDelete('restrict');
            $table->date('transaction_date');
            
            $table->string('vendor_name')->nullable();
            $table->string('invoice_path')->nullable(); // Uploaded document
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->foreign('wallet_id')->references('id')->on('gold_wallets')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gold_transactions');
    }
};
