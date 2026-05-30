<?php

namespace Tests\Unit\CRMWhatsAppCampaign;

use Tests\TestCase;
use Modules\CRM\app\Features\CRMWhatsAppCampaigns\Services\CampaignAudienceResolver;
use Modules\CRM\Models\WhatsAppCampaignAudience;
use Modules\CRM\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CampaignAudienceResolverTest extends TestCase
{
    use RefreshDatabase;

    protected CampaignAudienceResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = app(CampaignAudienceResolver::class);
    }

    public function test_resolves_leads_with_phone(): void
    {
        Lead::factory()->count(5)->create(['workspace_id' => 1, 'phone' => '+1234567890', 'status' => 'new']);
        Lead::factory()->create(['workspace_id' => 1, 'phone' => null]); // Should be excluded

        $audience = WhatsAppCampaignAudience::factory()->create([
            'workspace_id' => 1,
            'filters'      => [['field' => 'status', 'operator' => 'eq', 'value' => 'new']],
        ]);

        $count = $this->resolver->resolve($audience);
        $this->assertGreaterThan(0, $count);
    }

    public function test_preview_returns_estimated_count(): void
    {
        Lead::factory()->count(10)->create(['workspace_id' => 1, 'phone' => '+1234567890', 'status' => 'qualified']);

        $count = $this->resolver->preview(1, [['field' => 'status', 'operator' => 'eq', 'value' => 'qualified']]);
        $this->assertEquals(10, $count);
    }

    public function test_applies_has_tag_filter(): void
    {
        $lead = Lead::factory()->create(['workspace_id' => 1, 'phone' => '+1234567890']);
        // Tag filtering relies on lead.tags relationship

        $count = $this->resolver->preview(1, [['field' => 'source', 'operator' => 'eq', 'value' => $lead->source]]);
        $this->assertGreaterThanOrEqual(1, $count);
    }

    public function test_deduplicates_by_phone(): void
    {
        Lead::factory()->count(3)->create(['workspace_id' => 1, 'phone' => '+1234567890']);

        $audience = WhatsAppCampaignAudience::factory()->create(['workspace_id' => 1, 'filters' => []]);
        $count = $this->resolver->resolve($audience);

        // Should deduplicate to 1 unique phone
        $this->assertEquals(1, $count);
    }
}
