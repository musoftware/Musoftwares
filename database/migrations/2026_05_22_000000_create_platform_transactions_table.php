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
        Schema::create('platform_transactions', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('invoice_id')->nullable()->constrained('platform_invoices')->onDelete('cascade');
            
            $table->decimal('amount', 20, 8)->default(0);
            $table->decimal('business_amount', 20, 8)->default(0)->comment('Amount converted to business default currency');
            
            $table->string('currency', 3)->default('USD');
            $table->text('reason')->nullable();
            
            // type can be received, refunded, sent, used, earned
            $table->string('type')->default('received');
            
            // Reverse transactions support
            $table->foreignId('reverse_transaction_id')->nullable()->constrained('platform_transactions')->onDelete('set null');
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_transactions');
    }
};
