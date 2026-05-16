<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Settings
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Currencies
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique();
            $table->string('name');
            $table->string('symbol')->nullable();
            $table->decimal('exchange_rate', 20, 8)->default(1.0);
            $table->date('exchange_rate_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Ledgers
        Schema::create('ledgers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // asset, liability, equity, revenue, expense
            $table->timestamps();
        });

        // Accounts
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ledger_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->unique();
            $table->decimal('balance', 20, 8)->default(0);
            $table->string('currency_code', 3);
            $table->timestamps();
        });

        // Journal Entries
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('reference_type')->nullable();
            $table->string('reference_id')->nullable();
            $table->string('description');
            $table->date('date');
            $table->timestamps(); // Created at matters for audit
        });

        // Journal Entry Lines
        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->id();
            $table->uuid('journal_entry_id');
            $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();

            $table->decimal('debit', 20, 8)->default(0);
            $table->decimal('credit', 20, 8)->default(0);

            $table->decimal('amount', 20, 8);
            $table->string('amount_currency', 3);
            $table->decimal('business_amount', 20, 8);
            $table->string('business_currency', 3);
            $table->decimal('exchange_rate', 20, 8);
            $table->date('exchange_rate_date');

            $table->timestamps();
        });

        // Wallets
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->morphs('owner');
            $table->string('context')->default('default');
            $table->decimal('balance', 20, 8)->default(0);
            $table->string('currency', 3);
            $table->timestamps();
        });

        // Wallet Transactions (Immutable)
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['credit', 'debit']);
            $table->decimal('amount', 20, 8);

            $table->decimal('balance_before', 20, 8);
            $table->decimal('balance_after', 20, 8);

            $table->string('reference_type')->nullable();
            $table->string('reference_id')->nullable();
            $table->string('description');

            $table->timestamps();
        });

        // Conversations (Chat System)
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->morphs('conversable'); // polymorphic conversation
            $table->softDeletes();
            $table->timestamps();
        });

        // Conversation Participants
        Schema::create('conversation_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('last_read_at')->nullable();
            $table->timestamps();
        });

        // Messages
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->json('attachments')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        // Audit Logs
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
        });

        // Impersonation Logs
        Schema::create('impersonation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('impersonator_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('impersonated_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('impersonation_logs');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversation_participants');
        Schema::dropIfExists('conversations');
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('wallets');
        Schema::dropIfExists('journal_entry_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('ledgers');
        Schema::dropIfExists('currencies');
        Schema::dropIfExists('settings');
    }
};
