<?php

namespace Modules\PaymentGateway\Providers;

use App\Providers\BaseModuleServiceProvider;

class PaymentGatewayServiceProvider extends BaseModuleServiceProvider
{
    protected string $name = 'PaymentGateway';

    protected string $nameLower = 'payment-gateway';

    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();
        // the module finder looks in database/migrations, which Linux does not match with this module's folder
        $this->loadMigrationsFrom(module_path($this->name, 'Database/Migrations'));
    }
}
