<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables and their respective currency columns to rename to *_id
     */
    protected $tablesToRename = [
        'auto_sms_transactions' => ['currency' => 'currency_id'],
        'bookings' => ['currency' => 'currency_id'],
        'booking_daily_metrics' => ['currency' => 'currency_id'],
        'booking_event_types' => ['currency' => 'currency_id'],
        'cost_transactions' => ['currency' => 'currency_id'],
        'coupons' => ['currency' => 'currency_id'],
        'coupon_redemptions' => ['currency' => 'currency_id'],
        'earnings' => ['currency' => 'currency_id'],
        'earn_per_registers' => ['currency' => 'currency_id'],
        'client_wallets' => ['currency' => 'currency_id'],
        'tenant_clients' => ['currency' => 'currency_id'],
        'invoices' => ['currency' => 'currency_id'],
        'invoice_cost_accruals' => ['currency' => 'currency_id'],
        'invoice_item_timers' => ['currency' => 'currency_id'],
        'memberships' => ['currency' => 'currency_id'],
        'membership_users' => ['currency' => 'currency_id'],
        'merchant_orders' => ['currency' => 'currency_id'],
        'musoftware_payments' => ['currency' => 'currency_id'],
        'gateway_payments' => ['currency' => 'currency_id'],
        'payment_orders' => ['currency' => 'currency_id'],
        'payment_transactions' => ['currency' => 'currency_id'],
        'payment_webhook_logs' => ['currency' => 'currency_id'],
        'plans' => ['plan_currency' => 'plan_currency_id'],
        'point_supports' => ['currency' => 'currency_id'],
        'recurring_costs' => ['currency' => 'currency_id'],
        'recurring_incomes' => ['currency' => 'currency_id'],
        'recurring_salaries' => ['currency' => 'currency_id'],
        'saved_cards' => ['currency' => 'currency_id'],
        'sms_payment_gateway_transactions' => ['currency' => 'currency_id'],
        'tool_resellers' => ['currency' => 'currency_id'],
        'tool_reseller_transactions' => ['currency' => 'currency_id'],
        'tool_subscriptions' => ['currency' => 'currency_id'],
        'transactions' => ['currency' => 'currency_id'],
        'users' => [
            'hour_rate_currency' => 'hour_rate_currency_id',
            'booking_rate_currency' => 'booking_rate_currency_id',
        ],
        'user_money_transfers' => [
            'currency' => 'currency_id',
            'fee_currency' => 'fee_currency_id',
            'converted_currency' => 'converted_currency_id',
        ],
        'user_payment_methods' => ['currency' => 'currency_id'],
        'user_referral_request_withdraws' => ['currency' => 'currency_id'],
        'user_referral_commissions' => ['currency' => 'currency_id'],
        'vouchers' => [
            'spend_currency' => 'spend_currency_id',
            'reward_currency' => 'reward_currency_id',
        ],
        'voucher_redemptions' => [
            'spent_currency' => 'spent_currency_id',
            'reward_currency' => 'reward_currency_id',
        ],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->tablesToRename as $table => $columns) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $t) use ($table, $columns) {
                    foreach ($columns as $old => $new) {
                        if (Schema::hasColumn($table, $old) && ! Schema::hasColumn($table, $new)) {
                            // Safely rename column. In modern Laravel this usually works even with foreign keys,
                            // but if it fails due to FK constraints, we would need to drop and recreate the FKs.
                            $t->renameColumn($old, $new);
                        }
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->tablesToRename as $table => $columns) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $t) use ($table, $columns) {
                    foreach ($columns as $old => $new) {
                        if (Schema::hasColumn($table, $new) && ! Schema::hasColumn($table, $old)) {
                            $t->renameColumn($new, $old);
                        }
                    }
                });
            }
        }
    }
};
