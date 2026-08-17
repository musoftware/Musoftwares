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
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('quotation_number')->unique();
            $table->string('title');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->string('currency', 10)->default('USD');
            $table->decimal('deposit_percentage', 5, 2)->default(50.00);
            $table->decimal('development_total', 12, 2)->default(0.00);
            $table->decimal('indicative_total', 12, 2)->default(0.00);
            $table->decimal('grand_total', 12, 2)->default(0.00);
            $table->decimal('deposit_amount', 12, 2)->default(0.00);
            $table->decimal('remaining_amount', 12, 2)->default(0.00);
            $table->string('status', 30)->default('active')->index(); // active, draft, archived
            $table->date('valid_until')->nullable();
            $table->longText('scope_markdown')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('shortlink_id')->nullable()->index();
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamp('last_viewed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations')->cascadeOnDelete();
            $table->string('type', 30)->default('our_work')->index(); // our_work, indicative_cost
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2)->default(0.00);
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('total', 12, 2)->default(0.00);
            $table->string('external_link', 1000)->nullable();
            $table->string('link_label')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('quotation_orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('order_number')->unique();
            $table->foreignId('quotation_id')->constrained('quotations')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('client_name');
            $table->string('client_email');
            $table->string('client_phone')->nullable();
            $table->string('client_whatsapp')->nullable();
            $table->string('company_name')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('deposit_amount', 12, 2)->default(0.00);
            $table->string('currency', 10)->default('USD');
            $table->string('status', 30)->default('pending_payment')->index(); // pending_payment, paid, failed, cancelled
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->string('payment_gateway')->nullable();
            $table->string('payment_reference')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotation_orders');
        Schema::dropIfExists('quotation_items');
        Schema::dropIfExists('quotations');
    }
};
