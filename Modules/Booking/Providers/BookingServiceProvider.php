<?php

namespace Modules\Booking\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;
use Illuminate\Console\Scheduling\Schedule;

class BookingServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Booking';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'booking';

    /**
     * Command classes to register.
     *
     * @var string[]
     */
    // protected array $commands = [];

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    /**
     * Define module schedules.
     * 
     * @param $schedule
     */
    protected function configureSchedules(Schedule $schedule): void
    {
        // Run the WA Reminder job every minute to dispatch due reminders
        $schedule->job(new \Modules\Booking\app\Features\Reminders\Jobs\ProcessDueWaRemindersJob)->everyMinute();

        // Check pending custom domain verifications hourly
        $schedule->job(new \Modules\Booking\app\Features\CustomDomains\Jobs\VerifyCustomDomainJob)->hourly();
    }
}
