<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Add earned_balance to wallets
        if (Schema::hasTable('wallets')) {
            Schema::table('wallets', function (Blueprint $table) {
                if (!Schema::hasColumn('wallets', 'earned_balance')) {
                    $table->decimal('earned_balance', 20, 8)->default(0)->after('balance');
                }
            });
        }

        // Create payout_methods table
        if (!Schema::hasTable('payout_methods')) {
            Schema::create('payout_methods', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('type')->default('bank_transfer'); // bank_transfer, paypal, crypto_wallet
                $table->boolean('is_default')->default(false);
                $table->json('details')->nullable();
                $table->string('status')->default('approved'); // pending, approved, rejected
                $table->timestamps();
            });
        }

        // Create user_withdrawals table
        if (!Schema::hasTable('user_withdrawals')) {
            Schema::create('user_withdrawals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('payout_method_id')->constrained('payout_methods')->cascadeOnDelete();
                $table->decimal('amount', 20, 8);
                $table->string('currency', 3)->default('USD');
                $table->string('status')->default('pending'); // pending, approved, paid, rejected, cancelled
                $table->text('admin_note')->nullable();
                $table->string('reference')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('user_withdrawals');
        Schema::dropIfExists('payout_methods');
        if (Schema::hasTable('wallets')) {
            Schema::table('wallets', function (Blueprint $table) {
                if (Schema::hasColumn('wallets', 'earned_balance')) {
                    $table->dropColumn('earned_balance');
                }
            });
        }
    }
};
