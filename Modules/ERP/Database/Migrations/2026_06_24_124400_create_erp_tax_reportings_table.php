<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_tax_reportings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('tenant_id')->constrained('erp_tenants')->cascadeOnDelete();
            $table->string('report_name');
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('total_tax_collected', 15, 4)->default(0);
            $table->decimal('total_tax_paid', 15, 4)->default(0);
            $table->string('status')->default('draft');
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_tax_reportings');
    }
};
