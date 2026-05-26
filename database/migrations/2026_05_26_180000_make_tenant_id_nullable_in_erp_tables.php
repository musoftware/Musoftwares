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
        // 1. erp_invoices
        if (Schema::hasTable('erp_invoices')) {
            Schema::table('erp_invoices', function (Blueprint $table) {
                $table->dropForeign(['tenant_id']);
                $table->dropForeign(['client_id']);
            });
            
            Schema::table('erp_invoices', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->change();
                $table->unsignedBigInteger('client_id')->nullable()->change();
                $table->foreign('tenant_id')->references('id')->on('erp_tenants')->cascadeOnDelete();
            });
        }

        // 2. erp_invoice_items
        if (Schema::hasTable('erp_invoice_items')) {
            Schema::table('erp_invoice_items', function (Blueprint $table) {
                $table->dropForeign(['tenant_id']);
            });
            Schema::table('erp_invoice_items', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->change();
                $table->foreign('tenant_id')->references('id')->on('erp_tenants')->cascadeOnDelete();
            });
        }

        // 3. erp_invoice_costs
        if (Schema::hasTable('erp_invoice_costs')) {
            Schema::table('erp_invoice_costs', function (Blueprint $table) {
                $table->dropForeign(['tenant_id']);
            });
            Schema::table('erp_invoice_costs', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->change();
                $table->foreign('tenant_id')->references('id')->on('erp_tenants')->cascadeOnDelete();
            });
        }

        // 4. erp_client_wallets
        if (Schema::hasTable('erp_client_wallets')) {
            Schema::table('erp_client_wallets', function (Blueprint $table) {
                $table->dropForeign(['tenant_id']);
            });
            Schema::table('erp_client_wallets', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->change();
                $table->foreign('tenant_id')->references('id')->on('erp_tenants')->cascadeOnDelete();
            });
        }

        // 5. erp_client_wallet_transactions
        if (Schema::hasTable('erp_client_wallet_transactions')) {
            Schema::table('erp_client_wallet_transactions', function (Blueprint $table) {
                $table->dropForeign(['tenant_id']);
            });
            Schema::table('erp_client_wallet_transactions', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->change();
                $table->foreign('tenant_id')->references('id')->on('erp_tenants')->cascadeOnDelete();
            });
        }

        // 6. erp_expense_transactions
        if (Schema::hasTable('erp_expense_transactions')) {
            Schema::table('erp_expense_transactions', function (Blueprint $table) {
                $table->dropForeign(['tenant_id']);
            });
            Schema::table('erp_expense_transactions', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->change();
                $table->foreign('tenant_id')->references('id')->on('erp_tenants')->cascadeOnDelete();
            });
        }

        // 7. erp_projects
        if (Schema::hasTable('erp_projects')) {
            Schema::table('erp_projects', function (Blueprint $table) {
                $table->dropForeign(['tenant_id']);
                $table->dropForeign(['client_id']);
            });
            Schema::table('erp_projects', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->change();
                $table->unsignedBigInteger('client_id')->nullable()->change();
                $table->foreign('tenant_id')->references('id')->on('erp_tenants')->cascadeOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 7. erp_projects
        if (Schema::hasTable('erp_projects')) {
            Schema::table('erp_projects', function (Blueprint $table) {
                $table->dropForeign(['tenant_id']);
            });
            Schema::table('erp_projects', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->change();
                $table->unsignedBigInteger('client_id')->change();
                $table->foreign('tenant_id')->references('id')->on('erp_tenants')->cascadeOnDelete();
                $table->foreign('client_id')->references('id')->on('erp_tenant_clients')->cascadeOnDelete();
            });
        }

        // 6. erp_expense_transactions
        if (Schema::hasTable('erp_expense_transactions')) {
            Schema::table('erp_expense_transactions', function (Blueprint $table) {
                $table->dropForeign(['tenant_id']);
            });
            Schema::table('erp_expense_transactions', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->change();
                $table->foreign('tenant_id')->references('id')->on('erp_tenants')->cascadeOnDelete();
            });
        }

        // 5. erp_client_wallet_transactions
        if (Schema::hasTable('erp_client_wallet_transactions')) {
            Schema::table('erp_client_wallet_transactions', function (Blueprint $table) {
                $table->dropForeign(['tenant_id']);
            });
            Schema::table('erp_client_wallet_transactions', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->change();
                $table->foreign('tenant_id')->references('id')->on('erp_tenants')->cascadeOnDelete();
            });
        }

        // 4. erp_client_wallets
        if (Schema::hasTable('erp_client_wallets')) {
            Schema::table('erp_client_wallets', function (Blueprint $table) {
                $table->dropForeign(['tenant_id']);
            });
            Schema::table('erp_client_wallets', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->change();
                $table->foreign('tenant_id')->references('id')->on('erp_tenants')->cascadeOnDelete();
            });
        }

        // 3. erp_invoice_costs
        if (Schema::hasTable('erp_invoice_costs')) {
            Schema::table('erp_invoice_costs', function (Blueprint $table) {
                $table->dropForeign(['tenant_id']);
            });
            Schema::table('erp_invoice_costs', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->change();
                $table->foreign('tenant_id')->references('id')->on('erp_tenants')->cascadeOnDelete();
            });
        }

        // 2. erp_invoice_items
        if (Schema::hasTable('erp_invoice_items')) {
            Schema::table('erp_invoice_items', function (Blueprint $table) {
                $table->dropForeign(['tenant_id']);
            });
            Schema::table('erp_invoice_items', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->change();
                $table->foreign('tenant_id')->references('id')->on('erp_tenants')->cascadeOnDelete();
            });
        }

        // 1. erp_invoices
        if (Schema::hasTable('erp_invoices')) {
            Schema::table('erp_invoices', function (Blueprint $table) {
                $table->dropForeign(['tenant_id']);
            });
            Schema::table('erp_invoices', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->change();
                $table->unsignedBigInteger('client_id')->change();
                $table->foreign('tenant_id')->references('id')->on('erp_tenants')->cascadeOnDelete();
                $table->foreign('client_id')->references('id')->on('erp_tenant_clients')->cascadeOnDelete();
            });
        }
    }
};
