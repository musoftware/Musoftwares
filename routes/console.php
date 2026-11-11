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

Schedule::command(RenewSubscriptions::class)->dailyAt('03:00')->withoutOverlapping(10);
Schedule::command(\App\Console\Commands\SendSubscriptionExpiryReminders::class)->dailyAt('03:15')->timezone('Africa/Cairo');
Schedule::command(CleanupExpiredFbmbResults::class)->dailyAt('04:00');

// Recurring Business Commands
Schedule::command(\App\Console\Commands\AddRecurringCosts::class)->everyMinute()->withoutOverlapping(10);
Schedule::command(\App\Console\Commands\AddRecurringSalaries::class)->everyMinute()->withoutOverlapping(10);
Schedule::command(\App\Console\Commands\AddRecurringIncomes::class)->everyMinute()->withoutOverlapping(10);
Schedule::command(\App\Console\Commands\AddRecurringInvoices::class)->everyMinute()->withoutOverlapping(10);

// Process matured referral earnings every minute
Schedule::command(ProcessEarningsClearing::class)->everyMinute()->withoutOverlapping(10);

// Process pending FBMB database lookups every minute
Schedule::command(\Modules\Fbmb\Console\ProcessPendingFbmbLookups::class)->everyMinute()->withoutOverlapping(10);

// Auto-complete delivered marketplace orders hourly
Schedule::command(\App\Console\Commands\CompleteDeliveredMarketplaceOrders::class)->hourly();

// Cancel and refund marketplace orders whose cancellation request got no answer
Schedule::command(\App\Console\Commands\AcceptExpiredCancellationRequests::class)->hourly()->withoutOverlapping(10);

// Background translation of untranslated marketplace services hourly
Schedule::command(\App\Console\Commands\TranslateMarketplaceServices::class)->hourly()->withoutOverlapping(10);

// Tell IndexNow search engines about changed public pages (sends nothing when nothing changed)
Schedule::command(\App\Console\Commands\IndexNowSubmit::class)->dailyAt('03:30')->timezone('Africa/Cairo');

// One reminder for a store order that was started and not paid (2 to 48 hours old)
Schedule::command(\App\Console\Commands\SendSoftwareOrderReminders::class)->hourly()->withoutOverlapping(10);

// The AI builder: a customer who went quiet before paying gets a reminder email, then the discount by email
Schedule::call(fn () => app(\Modules\AppBuilder\Services\ChatFollowUp::class)->sendDue())
    ->name('builder-follow-ups')
    ->hourly()
    ->withoutOverlapping(30);

// The AI builder: a customer who closed the page on a price, with notifications on, hears the discount waits
Schedule::call(fn () => app(\Modules\AppBuilder\Services\LeaveDiscount::class)->sendReminders())
    ->name('builder-leave-reminders')
    ->everyFiveMinutes()
    ->withoutOverlapping(10);

// One blog article a day, Arabic and English in turn, written and published by AI; off with blog_autopilot_enabled = 0
Schedule::command(\App\Console\Commands\PublishBlogArticle::class)
    ->dailyAt('10:00')
    ->timezone('Africa/Cairo')
    ->when(fn () => \App\Console\Commands\PublishBlogArticle::enabled())
    ->withoutOverlapping(30);

// Send daily new services digest daily at 23:30 Cairo Time
Schedule::command(\App\Console\Commands\SendDailyNewServicesDigest::class)->dailyAt('23:30')->timezone('Africa/Cairo');

// Auto-pay unpaid invoices daily at 04:30 Cairo Time
Schedule::command(\App\Console\Commands\AutoPayInvoices::class)->dailyAt('04:30')->timezone('Africa/Cairo');

// Store: refresh Angazny products hourly and send waiting orders
Schedule::command('shop:angazny-sync')->hourly()->withoutOverlapping(30);
Schedule::command('shop:angazny-send-orders')->everyMinute()->withoutOverlapping(10);
Schedule::command('shop:angazny-drive-images')->everyTenMinutes()->withoutOverlapping(9);
Schedule::command('shop:rewrite-content')->everyTenMinutes()->withoutOverlapping(9);
Schedule::command('shop:arrange-images')->everyTenMinutes()->withoutOverlapping(9);

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

// Generate Marketplace AI agent files & robots.txt daily at 03:45 Cairo Time
Schedule::command('marketplace:generate-ai-files')
    ->dailyAt('03:45')
    ->timezone('Africa/Cairo')
    ->withoutOverlapping(10);

// Loyalty tier-progress and invoice-due reminders daily at 08:00 Cairo Time
Schedule::command(\App\Console\Commands\ProcessLoyaltyDueReminders::class)
    ->dailyAt('08:00')
    ->timezone('Africa/Cairo')
    ->withoutOverlapping(5);

// Win-back re-engagement campaigns daily at 09:00 Cairo Time
Schedule::command(\App\Console\Commands\ProcessWinbackCampaigns::class)
    ->dailyAt('09:00')
    ->timezone('Africa/Cairo')
    ->withoutOverlapping(5);

// Deduct loyalty points daily for overdue unpaid invoices marked as done at 00:05 Cairo Time
Schedule::command(\App\Console\Commands\DeductOverdueInvoiceLoyaltyPenalties::class)
    ->dailyAt('00:05')
    ->timezone('Africa/Cairo')
    ->withoutOverlapping(5);

// Client software winback campaigns daily at 09:30 Cairo Time
Schedule::command(\App\Console\Commands\ProcessClientSoftwareWinback::class)
    ->dailyAt('09:30')
    ->timezone('Africa/Cairo')
    ->withoutOverlapping(5);

// Automated PDPL data retention cleanup (expired OTPs/logs & abandoned account anonymization) daily at 02:30 Cairo Time
Schedule::command(\App\Console\Commands\EnforceDataRetentionPolicy::class)
    ->dailyAt('02:30')
    ->timezone('Africa/Cairo')
    ->withoutOverlapping(15);

// AES-256 encrypted database backups daily at 04:15 Cairo Time before external storage upload
Schedule::command(\App\Console\Commands\CreateEncryptedBackupCommand::class, [
    '--disk' => config('filesystems.disks.s3.key') ? 's3' : 'local',
])
    ->dailyAt('04:15')
    ->timezone('Africa/Cairo')
    ->when(fn () => \App\Models\AdminSettings::GetValue('encrypted_backups_enabled', '1') === '1')
    ->withoutOverlapping(30);






