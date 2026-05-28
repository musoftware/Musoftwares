<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add client_id and project_id to transactions table
        Schema::table('erp_client_wallet_transactions', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->after('wallet_id')->constrained('erp_tenant_clients')->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->after('client_id')->constrained('erp_projects')->nullOnDelete();
        });

        // 2. Migrate client_id from erp_client_wallets to transactions
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('UPDATE erp_client_wallet_transactions SET client_id = (SELECT client_id FROM erp_client_wallets WHERE erp_client_wallets.id = erp_client_wallet_transactions.wallet_id)');
        } else {
            DB::table('erp_client_wallet_transactions')
                ->join('erp_client_wallets', 'erp_client_wallet_transactions.wallet_id', '=', 'erp_client_wallets.id')
                ->update([
                    'erp_client_wallet_transactions.client_id' => DB::raw('erp_client_wallets.client_id')
                ]);
        }

        // 3. Make client_id non-nullable, drop wallet_id and balance columns
        Schema::table('erp_client_wallet_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('client_id')->nullable(false)->change();
            $table->dropForeign(['wallet_id']);
            $table->dropColumn('wallet_id');
            $table->dropColumn(['balance_before', 'balance_after']);
        });

        // 4. Drop client wallets table
        Schema::dropIfExists('erp_client_wallets');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Re-create erp_client_wallets table
        Schema::create('erp_client_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('erp_tenants')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('erp_tenant_clients')->cascadeOnDelete();
            $table->decimal('balance', 15, 2)->default(0);
            $table->decimal('locked_balance', 15, 2)->default(0);
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'client_id']);
        });

        // 2. Add columns back to erp_client_wallet_transactions
        Schema::table('erp_client_wallet_transactions', function (Blueprint $table) {
            $table->foreignId('wallet_id')->nullable()->after('id')->constrained('erp_client_wallets')->cascadeOnDelete();
            $table->decimal('balance_before', 15, 2)->default(0)->after('exchange_rate_date');
            $table->decimal('balance_after', 15, 2)->default(0)->after('balance_before');
        });

        // 3. Re-create wallets for existing clients in transactions and calculate balances
        $transactions = DB::table('erp_client_wallet_transactions')->get();
        $clientWallets = [];

        foreach ($transactions as $txn) {
            $key = $txn->tenant_id . '-' . $txn->client_id;
            if (!isset($clientWallets[$key])) {
                $walletId = DB::table('erp_client_wallets')->insertGetId([
                    'tenant_id' => $txn->tenant_id,
                    'client_id' => $txn->client_id,
                    'balance' => 0,
                    'currency_id' => $txn->currency_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $clientWallets[$key] = [
                    'id' => $walletId,
                    'balance' => 0.0,
                ];
            }

            $wallet = &$clientWallets[$key];
            $amount = (float) $txn->amount;
            $balBefore = $wallet['balance'];
            
            if ($txn->direction === 'credit') {
                $balAfter = $balBefore + $amount;
            } else {
                $balAfter = $balBefore - $amount;
            }

            DB::table('erp_client_wallet_transactions')
                ->where('id', $txn->id)
                ->update([
                    'wallet_id' => $wallet['id'],
                    'balance_before' => $balBefore,
                    'balance_after' => $balAfter,
                ]);

            $wallet['balance'] = $balAfter;
        }

        // Update the final balance on the erp_client_wallets
        foreach ($clientWallets as $key => $walletInfo) {
            DB::table('erp_client_wallets')
                ->where('id', $walletInfo['id'])
                ->update(['balance' => $walletInfo['balance']]);
        }

        // 4. Remove columns from erp_client_wallet_transactions
        Schema::table('erp_client_wallet_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('wallet_id')->nullable(false)->change();
            $table->dropForeign(['client_id']);
            $table->dropColumn('client_id');
            $table->dropForeign(['project_id']);
            $table->dropColumn('project_id');
        });
    }
};
