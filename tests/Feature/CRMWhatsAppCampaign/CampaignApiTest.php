<?php

namespace Tests\Feature\CRMWhatsAppCampaign;

use Tests\TestCase;
use App\Models\User;
use Modules\CRM\Models\WhatsAppCampaign;
use Modules\CRM\Models\WhatsAppCampaignTemplate;
use Modules\CRM\Models\WhatsAppCampaignAudience;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

class CampaignApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Event::fake();
        Queue::fake();
        session(['crm_workspace_id' => 1]);
    }

    public function test_can_list_campaigns(): void
    {
        WhatsAppCampaign::factory()->count(5)->create(['workspace_id' => 1]);

        $response = $this->actingAs($this->user)->get(route('crm.whatsapp-campaigns.index'));
        $response->assertStatus(200);
    }

    public function test_can_create_campaign(): void
    {
        $template = WhatsAppCampaignTemplate::factory()->create(['workspace_id' => 1]);
        $audience = WhatsAppCampaignAudience::factory()->create(['workspace_id' => 1]);

        $response = $this->actingAs($this->user)->post(route('crm.whatsapp-campaigns.store'), [
            'name'        => 'Summer Promo Campaign',
            'type'        => 'broadcast',
            'template_id' => $template->id,
            'audience_id' => $audience->id,
            'message_body' => 'Hello {{customer_name}}!',
            'account_id'  => 1,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('crm_wa_campaigns', ['name' => 'Summer Promo Campaign', 'status' => 'draft']);
    }

    public function test_can_show_campaign(): void
    {
        $campaign = WhatsAppCampaign::factory()->create(['workspace_id' => 1]);

        $response = $this->actingAs($this->user)->get(route('crm.whatsapp-campaigns.show', $campaign->id));
        $response->assertStatus(200);
    }

    public function test_can_update_draft_campaign(): void
    {
        $campaign = WhatsAppCampaign::factory()->create(['workspace_id' => 1, 'status' => 'draft']);

        $response = $this->actingAs($this->user)->put(route('crm.whatsapp-campaigns.update', $campaign->id), [
            'name' => 'Updated Campaign Name',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('crm_wa_campaigns', ['id' => $campaign->id, 'name' => 'Updated Campaign Name']);
    }

    public function test_can_delete_draft_campaign(): void
    {
        $campaign = WhatsAppCampaign::factory()->create(['workspace_id' => 1, 'status' => 'draft']);

        $response = $this->actingAs($this->user)->delete(route('crm.whatsapp-campaigns.destroy', $campaign->id));
        $response->assertRedirect();
        $this->assertSoftDeleted('crm_wa_campaigns', ['id' => $campaign->id]);
    }

    public function test_cannot_delete_running_campaign(): void
    {
        $campaign = WhatsAppCampaign::factory()->running()->create(['workspace_id' => 1]);

        $response = $this->actingAs($this->user)->delete(route('crm.whatsapp-campaigns.destroy', $campaign->id));
        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_can_start_campaign(): void
    {
        $audience = WhatsAppCampaignAudience::factory()->create(['workspace_id' => 1]);
        $campaign = WhatsAppCampaign::factory()->create([
            'workspace_id' => 1, 'status' => 'draft',
            'audience_id' => $audience->id, 'account_id' => 1,
            'message_body' => 'Hello!',
        ]);

        $response = $this->actingAs($this->user)->post(route('crm.whatsapp-campaigns.start', $campaign->id));
        $response->assertRedirect();
    }

    public function test_can_pause_running_campaign(): void
    {
        $campaign = WhatsAppCampaign::factory()->running()->create(['workspace_id' => 1]);

        $response = $this->actingAs($this->user)->post(route('crm.whatsapp-campaigns.pause', $campaign->id));
        $response->assertRedirect();
        $this->assertDatabaseHas('crm_wa_campaigns', ['id' => $campaign->id, 'status' => 'paused']);
    }

    public function test_can_resume_paused_campaign(): void
    {
        $campaign = WhatsAppCampaign::factory()->create([
            'workspace_id' => 1, 'status' => 'paused', 'paused_at' => now(),
        ]);

        $response = $this->actingAs($this->user)->post(route('crm.whatsapp-campaigns.resume', $campaign->id));
        $response->assertRedirect();
    }

    public function test_can_cancel_campaign(): void
    {
        $campaign = WhatsAppCampaign::factory()->running()->create(['workspace_id' => 1]);

        $response = $this->actingAs($this->user)->post(route('crm.whatsapp-campaigns.cancel', $campaign->id));
        $response->assertRedirect();
        $this->assertDatabaseHas('crm_wa_campaigns', ['id' => $campaign->id, 'status' => 'cancelled']);
    }

    public function test_can_duplicate_campaign(): void
    {
        $campaign = WhatsAppCampaign::factory()->completed()->create([
            'workspace_id' => 1, 'name' => 'Original Campaign',
        ]);

        $response = $this->actingAs($this->user)->post(route('crm.whatsapp-campaigns.duplicate', $campaign->id));
        $response->assertRedirect();
        $this->assertDatabaseHas('crm_wa_campaigns', ['name' => 'Original Campaign (Copy)', 'status' => 'draft']);
    }

    public function test_can_schedule_campaign(): void
    {
        $audience = WhatsAppCampaignAudience::factory()->create(['workspace_id' => 1]);
        $campaign = WhatsAppCampaign::factory()->create([
            'workspace_id' => 1, 'status' => 'draft',
            'audience_id' => $audience->id, 'account_id' => 1,
            'message_body' => 'Hello!',
        ]);

        $response = $this->actingAs($this->user)->post(
            route('crm.whatsapp-campaigns.schedule', $campaign->id),
            ['scheduled_at' => now()->addHour()->toDateTimeString()]
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('crm_wa_campaigns', ['id' => $campaign->id, 'status' => 'scheduled']);
    }

    public function test_validates_required_fields_on_create(): void
    {
        $response = $this->actingAs($this->user)->post(route('crm.whatsapp-campaigns.store'), []);
        $response->assertSessionHasErrors(['name', 'type']);
    }

    public function test_campaign_filter_by_status(): void
    {
        WhatsAppCampaign::factory()->count(3)->create(['workspace_id' => 1, 'status' => 'draft']);
        WhatsAppCampaign::factory()->running()->count(2)->create(['workspace_id' => 1]);

        $response = $this->actingAs($this->user)->get(route('crm.whatsapp-campaigns.index', ['status' => 'running']));
        $response->assertStatus(200);
    }
}
