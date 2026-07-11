<?php

namespace Tests\Feature\Booking\WhiteLabel;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Modules\Booking\app\Features\WhiteLabel\Http\Middleware\EnforceWhiteLabelLimits;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class TenantIsolationWhiteLabelTest extends TestCase
{
    use RefreshDatabase;

    public function test_enforce_white_label_limits_blocks_when_feature_disabled()
    {
        $user = User::factory()->create();

        $request = Request::create('/api/white-label/assets', 'POST');
        $request->attributes->set('tenant_id', 1);
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        // Mock feature() to return false
        config(['saas.addons.booking-white-label' => null]);

        $middleware = app(EnforceWhiteLabelLimits::class);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('White label feature is not enabled for your plan.');

        $middleware->handle($request, function () {}, 'asset');
    }
}
