<?php

namespace Modules\SmsPaymentGateway\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;
use Illuminate\Console\Scheduling\Schedule;

class SmsPaymentGatewayServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'SmsPaymentGateway';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'sms-payment-gateway';

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

    public function boot(): void
    {
        parent::boot();

        // Register the API key authentication middleware alias
        $router = $this->app['router'];
        $router->aliasMiddleware(
            'sms-gateway.api-key',
            \Modules\SmsPaymentGateway\Http\Middleware\AuthenticateApiKey::class
        );
    }

    /**
     * Define module schedules.
     * 
     * @param $schedule
     */
    // protected function configureSchedules(Schedule $schedule): void
    // {
    //     $schedule->command('inspire')->hourly();
    // }
}
