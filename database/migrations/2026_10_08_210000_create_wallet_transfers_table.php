<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * App\Models\WalletTransfer and WalletTransferService use this table, but no migration
 * ever created it. Guarded with hasTable so databases that already have it are untouched.
 * Ledger rows: user foreign keys restrict deletes (users use SoftDeletes).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('wallet_transfers')) {
            return;
        }

        Schema::create('wallet_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('receiver_id')->constrained('users')->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->decimal('fee_amount', 15, 2)->default(0);
            $table->string('currency', 10);
            $table->decimal('exchange_rate', 18, 6)->default(1);
            $table->decimal('converted_amount', 15, 2);
            $table->string('converted_currency', 10);
            $table->text('reason')->nullable();
            $table->string('status', 20)->default('completed');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['sender_id', 'status', 'created_at']);
            $table->index('receiver_id');
        });
    }

    public function down(): void
    {
        // Never drop a money ledger automatically; restore from backup if a rollback is needed.
    }
};
