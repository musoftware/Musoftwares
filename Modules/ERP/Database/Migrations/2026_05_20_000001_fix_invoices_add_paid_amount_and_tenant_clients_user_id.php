<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fix migration — two critical schema gaps:
 *
 * 1. invoices.paid_amount was missing → payment lifecycle (billInvoice,
 *    partiallyBillInvoice, cancelInvoice, unpaidAmount) all depend on it.
 *
 * 2. tenant_clients.user_id was missing → User::client() used an email
 *    join that could match the wrong client across tenants.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Fix 1: paid_amount on invoices ──────────────────────────
        if (!Schema::hasColumn('invoices', 'paid_amount')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->decimal('paid_amount', 15, 2)->default(0)->after('tax_amount');
            });
        }

        // ── Fix 2: user_id on tenant_clients ────────────────────────
        // Links a platform user (who is also a client in the ERP) to their
        // TenantClient record without relying on email matching.
        if (!Schema::hasColumn('tenant_clients', 'user_id')) {
            Schema::table('tenant_clients', function (Blueprint $table) {
                $table->foreignId('user_id')
                     ->nullable()
                     ->after('tenant_id')
                     ->constrained('users')
                     ->nullOnDelete();
            });
        }

        // ── Fix 3: locked_balance on client_wallets ──────────────────
        if (!Schema::hasColumn('client_wallets', 'locked_balance')) {
            Schema::table('client_wallets', function (Blueprint $table) {
                $table->decimal('locked_balance', 15, 2)->default(0)->after('balance');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('client_wallets', 'locked_balance')) {
            Schema::table('client_wallets', function (Blueprint $table) {
                $table->dropColumn('locked_balance');
            });
        }

        if (Schema::hasColumn('tenant_clients', 'user_id')) {
            Schema::table('tenant_clients', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            });
        }

        if (Schema::hasColumn('invoices', 'paid_amount')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropColumn('paid_amount');
            });
        }
    }
};
