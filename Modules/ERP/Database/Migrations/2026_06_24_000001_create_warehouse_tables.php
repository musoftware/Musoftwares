<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_warehouses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('country')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('erp_warehouse_zones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('warehouse_id')->constrained('erp_warehouses')->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('type')->nullable(); // receiving, storage, picking, shipping
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('erp_warehouse_bins', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('warehouse_zone_id')->constrained('erp_warehouse_zones')->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('barcode')->nullable();
            $table->decimal('max_weight', 10, 2)->nullable();
            $table->decimal('max_volume', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('erp_stock_transfers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('reference_number')->nullable();
            $table->foreignUuid('from_warehouse_id')->constrained('erp_warehouses')->cascadeOnDelete();
            $table->foreignUuid('to_warehouse_id')->constrained('erp_warehouses')->cascadeOnDelete();
            $table->string('status')->default('draft'); // draft, in_transit, completed, cancelled
            $table->dateTime('transfer_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('erp_stock_reservations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('erp_products')->cascadeOnDelete();
            $table->foreignUuid('warehouse_id')->constrained('erp_warehouses')->cascadeOnDelete();
            $table->decimal('quantity', 15, 2);
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('status')->default('active'); // active, fulfilled, cancelled
            $table->dateTime('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('erp_stock_adjustments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('warehouse_id')->constrained('erp_warehouses')->cascadeOnDelete();
            $table->string('reference_number')->nullable();
            $table->dateTime('adjustment_date')->nullable();
            $table->string('reason')->nullable();
            $table->string('status')->default('draft'); // draft, approved, cancelled
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('erp_inventory_counts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('warehouse_id')->constrained('erp_warehouses')->cascadeOnDelete();
            $table->string('reference_number')->nullable();
            $table->dateTime('count_date')->nullable();
            $table->string('status')->default('draft'); // draft, in_progress, completed, cancelled
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_inventory_counts');
        Schema::dropIfExists('erp_stock_adjustments');
        Schema::dropIfExists('erp_stock_reservations');
        Schema::dropIfExists('erp_stock_transfers');
        Schema::dropIfExists('erp_warehouse_bins');
        Schema::dropIfExists('erp_warehouse_zones');
        Schema::dropIfExists('erp_warehouses');
    }
};
