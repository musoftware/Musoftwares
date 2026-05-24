<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Console\Commands\FetchExchangeRates;
use Modules\ERP\Console\ProcessRecurringEntries;
use Modules\Freelance\Console\ExpireOldJobs;
use App\Console\Commands\RenewSubscriptions;
use Modules\fbmb\Console\CleanupExpiredFbmbResults;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command(FetchExchangeRates::class)->dailyAt('00:00');
Schedule::command(ProcessRecurringEntries::class)->dailyAt('01:00');
Schedule::command(ExpireOldJobs::class)->dailyAt('02:00');
Schedule::command(RenewSubscriptions::class)->dailyAt('03:00');
Schedule::command(\App\Console\Commands\RenewPlatformSubscriptions::class)->dailyAt('03:30');
Schedule::command(CleanupExpiredFbmbResults::class)->dailyAt('04:00');

