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
        $tables = [
            'currencies',
            'currencies_exchanges',
            'invoice_item_timers',
            'invoice_cost_lines',
            'invoice_cost_accruals',
            'charity_counters',
            'charity_transactions',
            'co_tech_tags',
            'co_workers',
            'conversion_rates',
            'coupon_redemptions',
            'coworker_messages',
            'device_tokens',
            'earnings',
            'files',
            'file_folders',
            'free_downloads',
            'gold_prices',
            'gold_world_prices',
            'kanban_tasks',
            'ku_coin_api_keys',
            'kyc_documents',
            'language_lines',
            'likes',
            'merchant_orders',
            'message_activity_reads',
            'musoftware_clients',
            'musoftware_payments',
            'notifications',
            'order_delivery_files',
            'payment_transactions',
            'pc_serials',
            'plans',
            'policy_agreements',
            'qr_codes',
            'quotation_orders',
            'recurring_busy_times',
            'recurring_costs',
            'recurring_incomes',
            'recurring_salaries',
            'requests',
            'reviews',
            'saved_cards',
            'saved_replies',
            'software_custom_values',
            'software_programs',
            'software_program_translations',
            'strategy_versions',
            'tasks',
            'team_members',
            'terms_agreements',
            'tickets',
            'todo_audio',
            'todo_images',
            'user_activities',
            'user_credentials',
            'user_payment_methods',
            'user_referrals',
            'user_referral_request_withdraws',
            'user_threads',
            'voucher_redemptions',
            'wallet_transfers',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->softDeletes();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'currencies',
            'currencies_exchanges',
            'invoice_item_timers',
            'invoice_cost_lines',
            'invoice_cost_accruals',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropSoftDeletes();
                });
            }
        }
    }
};
