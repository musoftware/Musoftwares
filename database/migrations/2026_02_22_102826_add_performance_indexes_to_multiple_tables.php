<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->addIndexIfNotExists('transactions', ['type', 'created_at'], 'idx_transactions_type_created');
        $this->addIndexIfNotExists('transactions', 'created_at', 'idx_transactions_created');

        $this->addIndexIfNotExists('invoice_item_timers', 'created_at', 'idx_timers_created');

        $this->addIndexIfNotExists('users', 'created_at', 'idx_users_created');
        $this->addIndexIfNotExists('users', 'subscription_date', 'idx_users_subscription');

        $this->addIndexIfNotExists('cost_transactions', 'created_at', 'idx_cost_transactions_created');

        $this->addIndexIfNotExists('recurring_incomes', 'current_date', 'idx_recurring_incomes_date');

        $this->addIndexIfNotExists('invoices', ['status', 'archive', 'created_at'], 'idx_invoices_status_archive_created');

        $this->addIndexIfNotExists('tickets', ['ticket_status', 'priority', 'created_at'], 'idx_tickets_status_priority_created');

        $this->addIndexIfNotExists('user_referral_request_withdraws', ['status', 'created_at'], 'idx_withdraw_status_created');

        $this->addIndexIfNotExists('user_activities', 'activity_date', 'idx_user_activities_date');
    }

    private function addIndexIfNotExists(string $table, $columns, string $indexName): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $sm = Schema::getConnection()->getSchemaBuilder();
        if (! $sm->hasIndex($table, $indexName)) {
            try {
                Schema::table($table, function (Blueprint $t) use ($columns, $indexName) {
                    $t->index($columns, $indexName);
                });
            } catch (QueryException $e) {
                // Error 1061 is Duplicate key name
                if ($e->errorInfo[1] !== 1061) {
                    throw $e;
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('idx_transactions_type_created');
            $table->dropIndex('idx_transactions_created');
        });

        Schema::table('invoice_item_timers', function (Blueprint $table) {
            $table->dropIndex('idx_timers_created');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_created');
            $table->dropIndex('idx_users_subscription');
        });

        Schema::table('cost_transactions', function (Blueprint $table) {
            $table->dropIndex('idx_cost_transactions_created');
        });

        Schema::table('recurring_incomes', function (Blueprint $table) {
            $table->dropIndex('idx_recurring_incomes_date');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('idx_invoices_status_archive_created');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex('idx_tickets_status_priority_created');
        });

        Schema::table('user_referral_request_withdraws', function (Blueprint $table) {
            $table->dropIndex('idx_withdraw_status_created');
        });

        Schema::table('user_activities', function (Blueprint $table) {
            $table->dropIndex('idx_user_activities_date');
        });
    }
};
