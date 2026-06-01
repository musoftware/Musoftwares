<?php

namespace Modules\Booking\Tests\Feature;

use Tests\TestCase;
use Modules\Booking\Models\TenantDomain;
use Illuminate\Http\Request;
use Modules\Booking\Http\Middleware\ResolveTenantDomain;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CustomDomainResolutionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_middleware_resolves_custom_domain_to_tenant()
    {
        TenantDomain::create([
            'tenant_id' => 5,
            'domain' => 'book.myclinic.com',
            'is_verified' => true,
        ]);

        $request = Request::create('http://book.myclinic.com/some-path', 'GET');
        
        $middleware = new ResolveTenantDomain();
        
        $response = $middleware->handle($request, function ($req) {
            return $req;
        });

        $this->assertTrue($response->attributes->has('custom_domain_tenant_id'));
        $this->assertEquals(5, $response->attributes->get('custom_domain_tenant_id'));
    }

    public function test_middleware_ignores_base_domain()
    {
        $baseDomain = config('app.url'); // e.g. http://localhost
        $cleanBaseDomain = preg_replace('#^https?://#', '', $baseDomain);

        $request = Request::create('http://' . $cleanBaseDomain . '/some-path', 'GET');
        
        $middleware = new ResolveTenantDomain();
        
        $response = $middleware->handle($request, function ($req) {
            return $req;
        });

        $this->assertFalse($response->attributes->has('custom_domain_tenant_id'));
    }
}
