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

// Background translation of untranslated marketplace services hourly
Schedule::command(\App\Console\Commands\TranslateMarketplaceServices::class)->hourly()->withoutOverlapping(10);

// Automatically generate AI blog articles daily at 02:00
Schedule::command(\App\Console\Commands\GenerateBlogArticles::class, ['--limit' => 5])->dailyAt('02:00')->withoutOverlapping(10);

// Send daily new services digest daily at 23:30 Cairo Time
Schedule::command(\App\Console\Commands\SendDailyNewServicesDigest::class)->dailyAt('23:30')->timezone('Africa/Cairo');

// Auto-pay unpaid invoices daily at 04:30 Cairo Time
Schedule::command(\App\Console\Commands\AutoPayInvoices::class)->dailyAt('04:30')->timezone('Africa/Cairo');

// Poll IMAP every two minutes for guest ticket replies
Schedule::command('imap:pull')->everyTwoMinutes()->withoutOverlapping(5);

// Scrape Waseet Egypt jobs listings daily at 02:00 Cairo Time
Schedule::command('listing:scrape-waseet')
    ->dailyAt('02:00')
    ->timezone('Africa/Cairo')
    ->withoutOverlapping(10);

// Check DSO limits daily at 01:00 Cairo Time
Schedule::command(\App\Console\Commands\CheckDsoLimits::class)
    ->dailyAt('01:00')
    ->timezone('Africa/Cairo');

// Decrement global DSO limit monthly on the 1st at 00:00 Cairo Time
Schedule::command(\App\Console\Commands\DecrementDsoLimit::class)
    ->monthlyOn(1, '00:00')
    ->timezone('Africa/Cairo');

// Send daily marketplace discount notifications to 30 random users daily at 12:00 Cairo Time
Schedule::command(\Modules\Marketplace\Console\SendDailyDiscountNotificationsCommand::class)
    ->dailyAt('12:00')
    ->timezone('Africa/Cairo');



