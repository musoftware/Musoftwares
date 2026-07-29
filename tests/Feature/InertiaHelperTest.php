<?php

namespace Tests\Feature;

use App\Helpers\InertiaHelper;
use App\Http\Middleware\ForceFullPageRedirect;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class InertiaHelperTest extends TestCase
{
    public function test_is_inertia_helper_detects_header(): void
    {
        $requestWithHeader = Request::create('/test', 'GET', [], [], [], ['HTTP_X_INERTIA' => 'true']);
        $this->app->instance('request', $requestWithHeader);

        $this->assertTrue(InertiaHelper::isInertia());

        $requestWithoutHeader = Request::create('/test', 'GET');
        $this->app->instance('request', $requestWithoutHeader);

        $this->assertFalse(InertiaHelper::isInertia());

        $this->app->forgetInstance('request');
    }

    public function test_force_full_page_redirect_middleware_intercepts_inertia_requests(): void
    {
        Route::get('/test-blade-route-inertia', fn () => response('blade content'))
            ->middleware(ForceFullPageRedirect::class);

        $response = $this->withHeaders(['X-Inertia' => 'true'])
            ->get('/test-blade-route-inertia');

        $response->assertStatus(409);
        $this->assertTrue($response->headers->has('X-Inertia-Location'));
    }

    public function test_force_full_page_redirect_middleware_allows_normal_browser_requests(): void
    {
        Route::get('/test-blade-route-normal', fn () => response('blade content'))
            ->middleware(ForceFullPageRedirect::class);

        $response = $this->get('/test-blade-route-normal');

        $response->assertStatus(200);
        $response->assertSee('blade content');
    }
}
