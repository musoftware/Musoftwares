<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_tax_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('tenant_id')->constrained('erp_tenants')->cascadeOnDelete();
            $table->string('name');
            $table->uuid('tax_rate_id')->nullable();
            $table->uuid('tax_group_id')->nullable();
            $table->json('condition')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_tax_rules');
    }
};
