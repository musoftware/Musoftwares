<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->index(['type', 'created_at'], 'idx_transactions_type_created');
            $table->index('created_at', 'idx_transactions_created');
        });

        Schema::table('invoice_item_timers', function (Blueprint $table) {
            $table->index('created_at', 'idx_timers_created');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('created_at', 'idx_users_created');
            $table->index('subscription_date', 'idx_users_subscription');
        });

        Schema::table('cost_transactions', function (Blueprint $table) {
            $table->index('created_at', 'idx_cost_transactions_created');
        });

        Schema::table('recurring_incomes', function (Blueprint $table) {
            $table->index('current_date', 'idx_recurring_incomes_date');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->index(['status', 'archive', 'created_at'], 'idx_invoices_status_archive_created');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->index(['ticket_status', 'priority', 'created_at'], 'idx_tickets_status_priority_created');
        });

        Schema::table('user_referral_request_withdraws', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'idx_withdraw_status_created');
        });

        Schema::table('user_activities', function (Blueprint $table) {
            $table->index('activity_date', 'idx_user_activities_date');
        });
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
