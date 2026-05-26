<?php

namespace Tests\Feature\CRMWhatsAppCampaign;

use Tests\TestCase;
use App\Models\User;
use Modules\CRM\Models\WhatsAppCampaignAudience;
use Modules\CRM\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AudienceApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        session(['crm_workspace_id' => 1]);
    }

    public function test_can_list_audiences(): void
    {
        WhatsAppCampaignAudience::factory()->count(3)->create(['workspace_id' => 1]);

        $response = $this->actingAs($this->user)->get(route('crm.whatsapp-campaigns.audiences.index'));
        $response->assertStatus(200);
    }

    public function test_can_create_audience(): void
    {
        $response = $this->actingAs($this->user)->post(route('crm.whatsapp-campaigns.audiences.store'), [
            'name'        => 'Hot Leads',
            'filters'     => [['field' => 'status', 'operator' => 'eq', 'value' => 'qualified']],
            'source_type' => 'leads',
            'is_dynamic'  => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('crm_wa_campaign_audiences', ['name' => 'Hot Leads']);
    }

    public function test_can_preview_audience_size(): void
    {
        Lead::factory()->count(5)->create(['workspace_id' => 1, 'phone' => '+1234567890', 'status' => 'new']);

        $response = $this->actingAs($this->user)->postJson(route('crm.whatsapp-campaigns.audiences.preview'), [
            'filters'     => [['field' => 'status', 'operator' => 'eq', 'value' => 'new']],
            'source_type' => 'leads',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['estimated_size']);
    }

    public function test_can_resolve_audience_members(): void
    {
        Lead::factory()->count(3)->create(['workspace_id' => 1, 'phone' => '+1111111111']);
        $audience = WhatsAppCampaignAudience::factory()->create(['workspace_id' => 1, 'filters' => []]);

        $response = $this->actingAs($this->user)->post(route('crm.whatsapp-campaigns.audiences.resolve', $audience->id));
        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_can_delete_audience(): void
    {
        $audience = WhatsAppCampaignAudience::factory()->create(['workspace_id' => 1]);

        $response = $this->actingAs($this->user)->delete(route('crm.whatsapp-campaigns.audiences.destroy', $audience->id));
        $response->assertRedirect();
    }

    public function test_validates_audience_filters(): void
    {
        $response = $this->actingAs($this->user)->post(route('crm.whatsapp-campaigns.audiences.store'), [
            'name' => 'Bad Audience',
        ]);

        $response->assertSessionHasErrors(['filters']);
    }
}
