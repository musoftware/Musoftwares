<?php

namespace Tests\Feature\Booking\WhiteLabel;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\Booking\app\Features\WhiteLabel\Services\WhiteLabelDomainResolver;
use Modules\Booking\app\Features\WhiteLabel\Models\WhiteLabelDomain;
use Modules\Booking\app\Features\WhiteLabel\Jobs\VerifyDomainDnsJob;
use Illuminate\Support\Facades\Queue;

class WhiteLabelDomainResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_adds_a_new_domain_and_generates_txt_record()
    {
        $resolver = app(WhiteLabelDomainResolver::class);
        $tenantId = 1;

        $domain = $resolver->addDomain($tenantId, 'book.custom.com');

        $this->assertInstanceOf(WhiteLabelDomain::class, $domain);
        $this->assertEquals('pending', $domain->status);
        $this->assertStringContainsString('musoftware-verification=', $domain->txt_record);
    }
}
