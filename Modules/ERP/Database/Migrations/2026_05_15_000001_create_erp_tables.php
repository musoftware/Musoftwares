<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. module_plans table
        Schema::create('module_plans', function (Blueprint $table) {
            $table->id();
            $table->enum('module', ['erp', 'freelance', 'marketplace']);
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->enum('billing', ['monthly', 'yearly']);
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. user_subscriptions table
        Schema::create('user_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('module_plans')->cascadeOnDelete();
            $table->enum('status', ['active', 'cancelled', 'expired']);
            $table->timestamp('started_at');
            $table->timestamp('expires_at')->nullable();
            $table->boolean('auto_renew')->default(true);
            $table->timestamps();
        });

        // 3. tenants table
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->enum('status', ['active', 'suspended', 'cancelled'])->default('active');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('subscription_ends_at')->nullable();
            $table->timestamps();
        });

        // 4. tenant_clients table
        Schema::create('tenant_clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('currency', 3);
            $table->string('country_code', 2)->nullable();
            $table->string('referral_code')->unique()->nullable();
            $table->foreignId('referred_by')->nullable()->constrained('tenant_clients')->nullOnDelete();
            $table->timestamps();
        });

        // 5. invoices table
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('invoice_number');
            $table->foreignId('client_id')->constrained('tenant_clients')->cascadeOnDelete();
            $table->enum('status', ['draft', 'sent', 'partial', 'paid', 'cancelled', 'refunded'])->default('draft');

            $table->decimal('amount', 15, 2)->default(0);
            $table->string('amount_currency', 3);
            $table->decimal('business_amount', 15, 2)->default(0);
            $table->string('business_currency', 3);
            $table->decimal('exchange_rate', 15, 6)->default(1);
            $table->date('exchange_rate_date');

            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);

            $table->date('due_date');
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['tenant_id', 'invoice_number']);
        });

        // 6. invoice_items table
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['simple', 'quantity', 'timer']);
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('unit_price', 15, 2);
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('total', 15, 2);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // 7. timer_sessions table
        Schema::create('timer_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_item_id')->constrained()->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('stopped_at')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->foreignId('started_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('stopped_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        // 8. invoice_costs table
        Schema::create('invoice_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();

            $table->decimal('amount', 15, 2);
            $table->string('amount_currency', 3);
            $table->decimal('business_amount', 15, 2);
            $table->string('business_currency', 3);
            $table->decimal('exchange_rate', 15, 6);
            $table->date('exchange_rate_date');

            $table->enum('payment_status', ['unpaid', 'paid'])->default('unpaid');
            $table->enum('payment_source', ['manual', 'client_balance'])->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();

            $table->text('note')->nullable();
            $table->timestamps();
        });

        // 9. client_wallets table
        Schema::create('client_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('tenant_clients')->cascadeOnDelete();
            $table->decimal('balance', 15, 2)->default(0);
            $table->string('currency', 3);
            $table->timestamps();

            $table->unique(['tenant_id', 'client_id']);
        });

        // 10. client_wallet_transactions table
        Schema::create('client_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wallet_id')->constrained('client_wallets')->cascadeOnDelete();

            $table->enum('type', [
                'invoice_issued', 'invoice_paid', 'invoice_refund',
                'commission_earned', 'commission_paid',
                'manual_credit', 'manual_debit',
                'withdrawal_requested', 'withdrawal_paid', 'withdrawal_cancelled',
                'cost_deducted'
            ]);
            $table->enum('direction', ['debit', 'credit']);

            $table->decimal('amount', 15, 2);
            $table->string('amount_currency', 3);
            $table->decimal('business_amount', 15, 2);
            $table->string('business_currency', 3);
            $table->decimal('exchange_rate', 15, 6);
            $table->date('exchange_rate_date');

            $table->decimal('balance_before', 15, 2);
            $table->decimal('balance_after', 15, 2);

            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('note')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            // Immutable
            $table->timestamp('created_at')->nullable();
        });


        // 11. expense_transactions table
        Schema::create('expense_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_cost_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('tenant_clients')->nullOnDelete();

            $table->enum('type', ['cost_recorded', 'cost_paid_manual', 'cost_paid_from_balance']);
            $table->enum('direction', ['debit', 'credit']);

            $table->decimal('amount', 15, 2);
            $table->string('amount_currency', 3);
            $table->decimal('business_amount', 15, 2);
            $table->string('business_currency', 3);
            $table->decimal('exchange_rate', 15, 6);
            $table->date('exchange_rate_date');

            $table->decimal('balance_before', 15, 2);
            $table->decimal('balance_after', 15, 2);

            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            // Immutable
            $table->timestamp('created_at')->nullable();
        });

        // 12. client_referrals table
        Schema::create('client_referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('referrer_id')->constrained('tenant_clients')->cascadeOnDelete();
            $table->foreignId('referee_id')->constrained('tenant_clients')->cascadeOnDelete();
            $table->tinyInteger('level'); // 1 or 2
            $table->enum('status', ['pending', 'active', 'cancelled'])->default('pending');
            $table->timestamps();
        });

        // 13. client_referral_earnings table
        Schema::create('client_referral_earnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('referrer_id')->constrained('tenant_clients')->cascadeOnDelete();
            $table->foreignId('referee_id')->constrained('tenant_clients')->cascadeOnDelete();

            $table->tinyInteger('level');

            $table->decimal('amount', 15, 2);
            $table->string('amount_currency', 3);
            $table->decimal('business_amount', 15, 2);
            $table->string('business_currency', 3);
            $table->decimal('exchange_rate', 15, 6);
            $table->date('exchange_rate_date');

            $table->decimal('commission_rate', 5, 2);
            $table->enum('status', ['pending', 'paid', 'cancelled'])->default('pending');

            $table->timestamps();
        });

        // 14. recurring_entries table
        Schema::create('recurring_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete(); // null = admin
            $table->enum('type', ['income', 'expense']);
            $table->string('title');
            $table->text('description')->nullable();

            $table->decimal('amount', 15, 2);
            $table->string('amount_currency', 3);
            $table->decimal('business_amount', 15, 2);
            $table->string('business_currency', 3);
            $table->decimal('exchange_rate', 15, 6);
            $table->date('exchange_rate_date');

            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'yearly']);
            $table->tinyInteger('frequency_day')->nullable();
            $table->tinyInteger('frequency_month')->nullable();

            $table->date('starts_at');
            $table->date('ends_at')->nullable();
            $table->date('next_run_at');
            $table->date('last_run_at')->nullable();

            $table->enum('status', ['active', 'paused', 'cancelled'])->default('active');
            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 15. recurring_execution_logs table
        Schema::create('recurring_execution_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recurring_entry_id')->constrained('recurring_entries')->cascadeOnDelete();
            $table->timestamp('executed_at');

            $table->decimal('amount', 15, 2);
            $table->string('amount_currency', 3);
            $table->decimal('business_amount', 15, 2);
            $table->string('business_currency', 3);
            $table->decimal('exchange_rate', 15, 6);
            $table->date('exchange_rate_date');

            $table->enum('status', ['success', 'failed']);
            $table->text('note')->nullable();

            // Immutable
            $table->timestamp('created_at')->nullable();
        });

        // 16. payment_methods table
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('tenant_clients')->cascadeOnDelete();

            $table->enum('type', ['bank_transfer']);
            $table->boolean('is_default')->default(false);
            $table->enum('status', ['pending_review', 'approved', 'rejected'])->default('pending_review');
            $table->text('rejection_note')->nullable();

            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();

            $table->string('bank_name');
            $table->string('account_holder_name');
            $table->string('account_number');
            $table->string('iban')->nullable();
            $table->string('swift_code')->nullable();
            $table->string('bank_country');
            $table->string('bank_currency');
            $table->string('branch_name')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 17. withdrawals table
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('tenant_clients')->cascadeOnDelete();
            $table->foreignId('payment_method_id')->constrained()->cascadeOnDelete();

            $table->string('status')->default('pending'); // pending, approved, paid, rejected, cancelled

            $table->decimal('amount', 15, 2);
            $table->string('currency_code', 3)->default('USD');
            $table->decimal('business_amount', 15, 2)->nullable();
            $table->string('business_currency', 3)->nullable();
            $table->decimal('exchange_rate', 15, 6)->nullable();
            $table->date('exchange_rate_date')->nullable();

            $table->decimal('balance_at_request', 15, 2)->nullable();

            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_note')->nullable();

            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->string('reference')->nullable();
            $table->string('proof_path')->nullable();

            $table->text('admin_notes')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('withdrawal_requests');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('recurring_execution_logs');
        Schema::dropIfExists('recurring_entries');
        Schema::dropIfExists('client_referral_earnings');
        Schema::dropIfExists('client_referrals');
        Schema::dropIfExists('expense_transactions');
        Schema::dropIfExists('client_wallet_transactions');
        Schema::dropIfExists('client_wallets');
        Schema::dropIfExists('invoice_costs');
        Schema::dropIfExists('timer_sessions');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('tenant_clients');
        Schema::dropIfExists('tenants');
        Schema::dropIfExists('user_subscriptions');
        Schema::dropIfExists('module_plans');
    }
};
