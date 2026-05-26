<?php

namespace Modules\PaymentGateway\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class PaymentGatewayServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'PaymentGateway';
    protected string $nameLower = 'payment-gateway';

    protected array $providers = [
        RouteServiceProvider::class,
    ];
}
