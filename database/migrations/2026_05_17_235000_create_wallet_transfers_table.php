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
        Schema::create('wallet_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('receiver_id')->constrained('users')->cascadeOnDelete();
            
            // Financial details
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3);
            $table->decimal('fee_amount', 15, 2)->default(0);
            $table->decimal('exchange_rate', 15, 6)->default(1.0);
            
            // Converted details (to handle cross-currency transfers securely)
            $table->decimal('converted_amount', 15, 2);
            $table->string('converted_currency', 3);
            
            // Security, Auditing & Metadata
            $table->string('reason', 500)->nullable();
            $table->string('status')->default('completed'); // completed, cancelled, failed
            $table->timestamp('processed_at')->useCurrent();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transfers');
    }
};
