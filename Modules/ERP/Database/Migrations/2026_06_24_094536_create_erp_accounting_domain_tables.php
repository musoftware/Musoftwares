<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('erp_fiscal_years')) {
            Schema::create('erp_fiscal_years', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
                $table->string('name');
                $table->date('start_date');
                $table->date('end_date');
                $table->boolean('is_closed')->default(false);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('erp_accounting_periods')) {
            Schema::create('erp_accounting_periods', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
                $table->foreignUuid('fiscal_year_id')->constrained('erp_fiscal_years')->cascadeOnDelete();
                $table->string('name');
                $table->date('start_date');
                $table->date('end_date');
                $table->boolean('is_closed')->default(false);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('erp_chart_of_accounts')) {
            Schema::create('erp_chart_of_accounts', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
                $table->string('code');
                $table->string('name');
                $table->enum('type', ['asset', 'liability', 'equity', 'revenue', 'expense']);
                $table->string('sub_type')->nullable();
                $table->foreignUuid('parent_id')->nullable()->constrained('erp_chart_of_accounts')->nullOnDelete();
                $table->string('currency_code')->default('USD');
                $table->boolean('is_active')->default(true);
                $table->text('description')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['tenant_id', 'code']);
            });
        }

        
        if (!Schema::hasTable('erp_accounting_rules')) {
            Schema::create('erp_accounting_rules', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('event_name');
                $table->string('condition')->nullable();
                $table->foreignUuid('debit_account_id')->constrained('erp_chart_of_accounts');
                $table->foreignUuid('credit_account_id')->constrained('erp_chart_of_accounts');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('erp_general_ledgers')) {
            Schema::create('erp_general_ledgers', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
                $table->string('name');
                $table->string('currency_code')->default('USD');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('erp_journal_entries')) {
            Schema::create('erp_journal_entries', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
                $table->foreignUuid('ledger_id')->nullable()->constrained('erp_general_ledgers')->nullOnDelete();
                $table->foreignUuid('accounting_period_id')->nullable()->constrained('erp_accounting_periods')->nullOnDelete();
                $table->string('entry_number');
                $table->string('reference')->nullable();
                $table->text('description')->nullable();
                $table->date('entry_date');
                $table->string('currency_code')->default('USD');
                $table->decimal('exchange_rate', 15, 6)->default(1.000000);
                $table->enum('status', ['draft', 'posted', 'cancelled'])->default('draft');
                $table->nullableUuidMorphs('document');
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['tenant_id', 'entry_number']);
            });
        }

        if (!Schema::hasTable('erp_journal_entry_lines')) {
            Schema::create('erp_journal_entry_lines', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
                $table->foreignUuid('journal_entry_id')->constrained('erp_journal_entries')->cascadeOnDelete();
                $table->foreignUuid('chart_of_account_id')->constrained('erp_chart_of_accounts')->restrictOnDelete();
                $table->text('description')->nullable();
                $table->decimal('debit', 15, 4)->default(0);
                $table->decimal('credit', 15, 4)->default(0);
                $table->decimal('base_debit', 15, 4)->default(0);
                $table->decimal('base_credit', 15, 4)->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('erp_bank_accounts')) {
            Schema::create('erp_bank_accounts', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
                $table->foreignUuid('chart_of_account_id')->nullable()->constrained('erp_chart_of_accounts')->nullOnDelete();
                $table->string('bank_name');
                $table->string('account_name');
                $table->string('account_number');
                $table->string('routing_number')->nullable();
                $table->string('swift_code')->nullable();
                $table->string('currency_code')->default('USD');
                $table->decimal('current_balance', 15, 4)->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('erp_bank_reconciliations')) {
            Schema::create('erp_bank_reconciliations', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
                $table->foreignUuid('bank_account_id')->constrained('erp_bank_accounts')->cascadeOnDelete();
                $table->date('statement_date');
                $table->decimal('statement_balance', 15, 4)->default(0);
                $table->decimal('reconciled_balance', 15, 4)->default(0);
                $table->enum('status', ['draft', 'reconciled'])->default('draft');
                $table->timestamps();
                $table->softDeletes();
            });
        }

    }

    public function down(): void
    {
        Schema::dropIfExists('erp_bank_reconciliations');
        Schema::dropIfExists('erp_bank_accounts');
        Schema::dropIfExists('erp_journal_entry_lines');
        Schema::dropIfExists('erp_journal_entries');
        Schema::dropIfExists('erp_general_ledgers');
        Schema::dropIfExists('erp_accounting_rules');
        Schema::dropIfExists('erp_chart_of_accounts');
        Schema::dropIfExists('erp_accounting_periods');
        Schema::dropIfExists('erp_fiscal_years');
    }
};
