<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Console\Commands\FetchExchangeRates;
use App\Console\Commands\RenewSubscriptions;
use App\Console\Commands\ProcessEarningsClearing;
use Modules\Fbmb\Console\CleanupExpiredFbmbResults;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command(FetchExchangeRates::class)->dailyAt('00:00');

Schedule::command(RenewSubscriptions::class)->dailyAt('03:00');
Schedule::command(\App\Console\Commands\RenewPlatformSubscriptions::class)->dailyAt('03:30');
Schedule::command(CleanupExpiredFbmbResults::class)->dailyAt('04:00');

// Recurring Business Commands
Schedule::command(\App\Console\Commands\AddRecurringCosts::class)->everyMinute();
Schedule::command(\App\Console\Commands\AddRecurringSalaries::class)->everyMinute();
Schedule::command(\App\Console\Commands\AddRecurringIncomes::class)->everyMinute();
Schedule::command(\App\Console\Commands\AddRecurringInvoices::class)->everyMinute();

// Process matured referral earnings every minute
Schedule::command(ProcessEarningsClearing::class)->everyMinute();

// Process pending FBMB database lookups every minute
Schedule::command(\Modules\Fbmb\Console\ProcessPendingFbmbLookups::class)->everyMinute();

// Auto-complete delivered marketplace orders hourly
Schedule::command(\App\Console\Commands\CompleteDeliveredMarketplaceOrders::class)->hourly();

// Gold Savers Jobs
Schedule::command(\Modules\GoldSavers\app\Console\Commands\FetchLocalGoldPrices::class)->hourly();
Schedule::command(\Modules\GoldSavers\app\Console\Commands\FetchGlobalGoldPrices::class)->hourly();

// Legacy Gold Commands
Schedule::command(\App\Console\Commands\GoldPriceFetcher::class)->everySixHours();
Schedule::command(\App\Console\Commands\GoldWorldPriceFetcher::class)->everySixHours();
