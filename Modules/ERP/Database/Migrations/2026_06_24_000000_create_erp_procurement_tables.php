<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_suppliers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('tenant_id')->constrained('erp_tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->string('tax_number')->nullable();
            $table->string('status')->default('active'); // active, inactive
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('erp_supplier_contacts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('supplier_id')->constrained('erp_suppliers')->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('designation')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('erp_purchase_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('tenant_id')->constrained('erp_tenants')->cascadeOnDelete();
            $table->string('request_number');
            $table->foreignId('requester_id')->constrained('erp_team_members')->cascadeOnDelete();
            $table->string('status')->default('draft'); // draft, submitted, approved, rejected
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('erp_purchase_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('tenant_id')->constrained('erp_tenants')->cascadeOnDelete();
            $table->foreignUuid('supplier_id')->constrained('erp_suppliers')->cascadeOnDelete();
            $table->foreignUuid('purchase_request_id')->nullable()->constrained('erp_purchase_requests')->nullOnDelete();
            $table->string('po_number');
            $table->string('status')->default('draft'); // draft, sent, accepted, partial_receipt, full_receipt, billed, cancelled
            
            // Dual currency
            $table->foreignId('currency_id')->constrained('currencies');
            $table->decimal('exchange_rate', 15, 6)->default(1.0);
            
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0); // Client currency
            $table->decimal('business_total_amount', 15, 2)->default(0); // Base currency
            
            $table->date('expected_delivery_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('erp_purchase_order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('purchase_order_id')->constrained('erp_purchase_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('erp_products')->nullOnDelete();
            $table->string('description');
            $table->decimal('quantity', 15, 2)->default(1);
            
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('business_unit_price', 15, 2)->default(0);
            
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('business_total_amount', 15, 2)->default(0);
            
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('erp_goods_receipt_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('tenant_id')->constrained('erp_tenants')->cascadeOnDelete();
            $table->foreignUuid('purchase_order_id')->constrained('erp_purchase_orders')->cascadeOnDelete();
            $table->string('grn_number');
            $table->date('received_date');
            $table->string('status')->default('draft'); // draft, received, partial
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('erp_vendor_bills', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('tenant_id')->constrained('erp_tenants')->cascadeOnDelete();
            $table->foreignUuid('supplier_id')->constrained('erp_suppliers')->cascadeOnDelete();
            $table->foreignUuid('purchase_order_id')->nullable()->constrained('erp_purchase_orders')->nullOnDelete();
            $table->string('bill_number');
            $table->string('status')->default('draft'); // draft, open, paid, partial_paid, cancelled
            
            // Dual currency
            $table->foreignId('currency_id')->constrained('currencies');
            $table->decimal('exchange_rate', 15, 6)->default(1.0);
            
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('business_total_amount', 15, 2)->default(0);
            
            $table->date('issue_date')->nullable();
            $table->date('due_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_vendor_bills');
        Schema::dropIfExists('erp_goods_receipt_notes');
        Schema::dropIfExists('erp_purchase_order_items');
        Schema::dropIfExists('erp_purchase_orders');
        Schema::dropIfExists('erp_purchase_requests');
        Schema::dropIfExists('erp_supplier_contacts');
        Schema::dropIfExists('erp_suppliers');
    }
};
