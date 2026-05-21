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
        Schema::create('platform_invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            
            $table->string('invoice_number')->unique();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            
            $table->decimal('amount', 20, 8)->default(0);
            $table->decimal('paid_amount', 20, 8)->default(0);
            $table->string('currency', 3)->default('USD');
            
            $table->enum('status', ['unpaid', 'partially_paid', 'paid', 'cancelled'])->default('unpaid');
            
            $table->date('due_date')->nullable();
            $table->date('issued_at')->nullable();
            $table->dateTime('paid_at')->nullable();
            
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
        });

        Schema::create('platform_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('platform_invoices')->cascadeOnDelete();
            
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price', 20, 8)->default(0);
            $table->decimal('total', 20, 8)->default(0);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_invoice_items');
        Schema::dropIfExists('platform_invoices');
    }
};
