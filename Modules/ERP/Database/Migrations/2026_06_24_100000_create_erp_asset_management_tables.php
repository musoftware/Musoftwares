<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_asset_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('tenant_id')->constrained('erp_tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->string('depreciation_method')->default('straight_line');
            $table->decimal('depreciation_rate', 5, 2)->nullable();
            $table->integer('useful_life_years')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('erp_fixed_assets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('tenant_id')->constrained('erp_tenants')->cascadeOnDelete();
            $table->foreignUuid('asset_category_id')->constrained('erp_asset_categories')->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('serial_number')->nullable();
            $table->date('purchase_date');
            $table->decimal('purchase_cost', 15, 2);
            $table->decimal('current_value', 15, 2);
            $table->decimal('salvage_value', 15, 2)->default(0);
            $table->string('location')->nullable();
            $table->string('status')->default('active'); // active, depreciating, disposed, etc.
            $table->foreignId('assigned_to')->nullable()->constrained('erp_team_members')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('erp_depreciation_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('tenant_id')->constrained('erp_tenants')->cascadeOnDelete();
            $table->foreignUuid('fixed_asset_id')->constrained('erp_fixed_assets')->cascadeOnDelete();
            $table->date('depreciation_date');
            $table->decimal('depreciation_amount', 15, 2);
            $table->decimal('accumulated_depreciation', 15, 2);
            $table->decimal('book_value', 15, 2);
            $table->boolean('is_posted')->default(false);
            $table->uuid('journal_entry_id')->nullable(); // referencing accounting journals
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('erp_asset_transfers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('tenant_id')->constrained('erp_tenants')->cascadeOnDelete();
            $table->foreignUuid('fixed_asset_id')->constrained('erp_fixed_assets')->cascadeOnDelete();
            $table->string('from_location')->nullable();
            $table->string('to_location')->nullable();
            $table->foreignId('from_employee_id')->nullable()->constrained('erp_team_members')->nullOnDelete();
            $table->foreignId('to_employee_id')->nullable()->constrained('erp_team_members')->nullOnDelete();
            $table->date('transfer_date');
            $table->text('reason')->nullable();
            $table->string('status')->default('pending'); // pending, approved, completed
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('erp_asset_disposals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('tenant_id')->constrained('erp_tenants')->cascadeOnDelete();
            $table->foreignUuid('fixed_asset_id')->constrained('erp_fixed_assets')->cascadeOnDelete();
            $table->date('disposal_date');
            $table->string('disposal_type'); // sold, scrapped, lost
            $table->decimal('disposal_value', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->uuid('journal_entry_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_asset_disposals');
        Schema::dropIfExists('erp_asset_transfers');
        Schema::dropIfExists('erp_depreciation_schedules');
        Schema::dropIfExists('erp_fixed_assets');
        Schema::dropIfExists('erp_asset_categories');
    }
};
