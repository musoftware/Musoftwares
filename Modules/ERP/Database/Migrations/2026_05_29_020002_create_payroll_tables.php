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
        Schema::create('erp_payroll_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('erp_tenants')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('erp_team_members')->cascadeOnDelete();
            $table->foreignId('currency_id')->constrained('currencies')->restrictOnDelete();
            $table->decimal('base_salary', 15, 2)->default(0);
            $table->string('payment_frequency')->default('monthly'); // monthly, weekly, bi-weekly
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // A member can only have one active contract
            $table->unique(['tenant_id', 'member_id']);
        });

        Schema::create('erp_payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('erp_tenants')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('erp_team_members')->cascadeOnDelete();
            $table->foreignId('currency_id')->constrained('currencies')->restrictOnDelete();
            $table->foreignId('payment_method_id')->nullable()->constrained('erp_payment_methods')->nullOnDelete();
            $table->integer('month');
            $table->integer('year');
            $table->integer('worked_days')->default(0);
            $table->integer('absent_days')->default(0);
            $table->decimal('base_amount', 15, 2)->default(0);
            $table->decimal('net_amount', 15, 2)->default(0);
            $table->enum('status', ['draft', 'paid', 'cancelled'])->default('draft');
            $table->date('paid_at')->nullable();
            $table->timestamps();

            // Prevent duplicate payslips for the same month/year per member
            $table->unique(['tenant_id', 'member_id', 'month', 'year']);
        });

        Schema::create('erp_payslip_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payslip_id')->constrained('erp_payslips')->cascadeOnDelete();
            $table->enum('type', ['bonus', 'deduction']);
            $table->decimal('amount', 15, 2);
            $table->string('description');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erp_payslip_items');
        Schema::dropIfExists('erp_payslips');
        Schema::dropIfExists('erp_payroll_contracts');
    }
};
