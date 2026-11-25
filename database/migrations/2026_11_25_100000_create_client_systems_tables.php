<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A client's own system (any language) that we license: a monthly price and/or a share of its sales.
 * The system checks in with us, gets a signed license, and locks itself when a bill stays unpaid.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_systems', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('site_url', 255)->nullable();
            $table->unsignedBigInteger('currency_id');
            $table->decimal('monthly_price', 12, 2)->default(0);
            $table->decimal('sales_percent', 5, 2)->default(0);
            $table->unsignedTinyInteger('grace_days')->default(7);
            $table->string('api_key', 64)->unique();
            $table->text('api_secret');
            $table->string('signing_public_key', 64);
            $table->text('signing_secret_key');
            $table->timestamp('next_bill_at')->nullable()->index();
            $table->timestamp('open_until')->nullable();
            $table->boolean('manually_locked')->default(false);
            $table->timestamp('last_check_in_at')->nullable();
            $table->string('last_check_in_ip', 45)->nullable();
            $table->string('fingerprint', 128)->nullable();
            $table->string('pending_fingerprint', 128)->nullable();
            $table->timestamp('fingerprint_changed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('client_system_bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_system_id')->constrained('client_systems')->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->timestamp('period_start');
            $table->timestamp('period_end');
            $table->decimal('monthly_fee', 12, 2)->default(0);
            $table->decimal('sales_total', 15, 2)->default(0);
            $table->decimal('sales_percent', 5, 2)->default(0);
            $table->decimal('sales_fee', 12, 2)->default(0);
            $table->timestamp('due_at');
            $table->timestamp('lock_at');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['client_system_id', 'period_start']);
        });

        Schema::create('client_system_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_system_id')->constrained('client_systems')->cascadeOnDelete();
            $table->foreignId('client_system_bill_id')->nullable()->constrained('client_system_bills')->nullOnDelete();
            $table->string('external_id', 100);
            $table->decimal('amount', 15, 2);
            $table->string('status', 20)->default('paid');
            $table->timestamp('sold_at');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['client_system_id', 'external_id']);
            $table->index(['client_system_id', 'client_system_bill_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_system_sales');
        Schema::dropIfExists('client_system_bills');
        Schema::dropIfExists('client_systems');
    }
};
