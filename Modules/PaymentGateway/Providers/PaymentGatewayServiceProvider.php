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
}
