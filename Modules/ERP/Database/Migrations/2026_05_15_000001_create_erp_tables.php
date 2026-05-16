<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Tenants
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('domain')->unique()->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Clients
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
        });

        // Subscriptions
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('plan_name');
            $table->decimal('price', 20, 8);
            $table->string('currency_code', 3);
            $table->string('status'); // active, canceled, past_due
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });

        // Invoices
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('number'); // per-tenant unique
            $table->date('issue_date');
            $table->date('due_date');
            $table->string('status'); // draft, sent, paid, overdue

            $table->decimal('subtotal', 20, 8)->default(0);
            $table->decimal('tax_amount', 20, 8)->default(0);
            $table->decimal('total', 20, 8)->default(0);
            $table->string('currency_code', 3);

            $table->timestamps();

            $table->unique(['tenant_id', 'number']);
        });

        // Invoice Items
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->decimal('quantity', 20, 8)->default(1);
            $table->decimal('unit_price', 20, 8);
            $table->decimal('tax_rate', 5, 2)->default(0); // 0-100 percentage
            $table->decimal('total', 20, 8);
            $table->timestamps();
        });

        // Timer Sessions (for billing)
        Schema::create('timer_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('task_description');
            $table->timestamp('started_at');
            $table->timestamp('stopped_at')->nullable();
            $table->integer('duration_seconds')->default(0);
            $table->decimal('hourly_rate', 20, 8)->default(0);
            $table->boolean('is_billed')->default(false);
            $table->foreignId('invoice_item_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        // Recurring Entries
        Schema::create('recurring_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['income', 'expense']);
            $table->string('description');
            $table->decimal('amount', 20, 8);
            $table->string('currency_code', 3);
            $table->string('frequency'); // daily, weekly, monthly, yearly
            $table->date('next_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Expense Tracking
        Schema::create('expense_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->decimal('amount', 20, 8);
            $table->string('currency_code', 3);
            $table->date('date');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('expense_transactions');
        Schema::dropIfExists('recurring_entries');
        Schema::dropIfExists('timer_sessions');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('clients');
        Schema::dropIfExists('tenants');
    }
};
