<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 2. currencies table
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique();
            $table->string('name');
            $table->string('symbol')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 3. exchange_rates table
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->string('from_currency', 3);
            $table->string('to_currency', 3);
            $table->decimal('rate', 15, 6);
            $table->date('effective_date');
            $table->enum('source', ['manual', 'api_auto']);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['from_currency', 'to_currency', 'effective_date']);
        });

        // 4. site_settings table
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->enum('group', ['general', 'currency', 'referral', 'service', 'withdrawal', 'points']);
            $table->timestamps();
        });

        // 5. support_tickets table
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->string('subject');
            $table->enum('status', ['open', 'pending', 'resolved', 'closed']);
            $table->enum('priority', ['low', 'medium', 'high', 'urgent']);
            $table->timestamps();
        });

        // 6. conversations table
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->morphs('conversable'); // conversable_type, conversable_id
            $table->enum('type', ['marketplace_order', 'freelance_contract', 'support_ticket']);
            $table->enum('status', ['open', 'closed', 'archived']);
            $table->timestamps();
        });

        // 7. conversation_participants table
        Schema::create('conversation_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['client', 'freelancer', 'seller', 'buyer', 'admin']);
            $table->timestamp('last_read_at')->nullable();
            $table->timestamps();
        });

        // 8. messages table
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->text('body')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        // 9. message_attachments table
        Schema::create('message_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['image', 'file']);
            $table->string('path');
            $table->string('mime_type');
            $table->bigInteger('size_bytes');
            $table->string('original_name');
            $table->timestamps();
        });

        // Account / Ledger setup
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
            $table->timestamps();
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
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('wallets');
        Schema::dropIfExists('journal_entry_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('ledgers');
        Schema::dropIfExists('message_attachments');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversation_participants');
        Schema::dropIfExists('conversations');
        Schema::dropIfExists('support_tickets');
        Schema::dropIfExists('site_settings');
        Schema::dropIfExists('exchange_rates');
        Schema::dropIfExists('currencies');
    }
};
