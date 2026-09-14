<?php

namespace Tests\Feature;

use App\Models\ClientVaultAsset;
use App\Models\Currency;
use App\Models\LoyaltyReward;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TierPriorityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ClientPortalLoyaltyAndTiersTest extends TestCase
{
    use RefreshDatabase;

    protected Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();

        $this->currency = Currency::firstOrCreate(
            ['currency' => 'USD'],
            ['name' => 'US Dollar', 'symbol' => '$']
        );
    }

    protected function createClient(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'currency_id' => $this->currency->id,
            'tier' => 'standard',
            'lifetime_spend' => 0.00,
            'loyalty_points_balance' => 0,
            'profile_completion_percentage' => 25,
        ], $attributes));
    }

    public function test_client_can_get_loyalty_overview_and_rewards_catalog(): void
    {
        $user = $this->createClient([
            'loyalty_points_balance' => 350,
            'tier' => 'pro',
        ]);

        LoyaltyReward::create([
            'name' => '15% Off Next Invoice',
            'reward_type' => 'invoice_discount',
            'points_cost' => 300,
            'discount_value' => 15.00,
            'discount_type' => 'percentage',
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $overviewRes = $this->getJson('/api/portal/loyalty/overview');
        $overviewRes->assertStatus(200)
            ->assertJsonPath('data.balance', 350)
            ->assertJsonPath('data.tier', 'pro');

        $rewardsRes = $this->getJson('/api/portal/loyalty/rewards');
        $rewardsRes->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['name' => '15% Off Next Invoice']);
    }

    public function test_client_awarded_points_on_self_service_ticket_and_vip_priority_assigned(): void
    {
        $standardUser = $this->createClient([
            'tier' => 'standard',
            'lifetime_spend' => 500.00,
        ]);

        $enterpriseUser = $this->createClient([
            'tier' => 'enterprise',
            'lifetime_spend' => 15000.00,
        ]);

        // 1. Standard user submits ticket
        Sanctum::actingAs($standardUser);
        $resStd = $this->postJson('/api/portal/tickets', [
            'ticket_subject' => 'Help with webhook connection',
            'ticket_message' => 'Our webhook is returning 500 when payload is sent.',
            'urgency' => 'normal',
        ]);

        $resStd->assertStatus(201);
        $this->assertEquals(15, $standardUser->fresh()->loyalty_points_balance);
        $stdTicket = Ticket::latest('id')->first();
        $this->assertTrue($stdTicket->is_self_service);

        // 2. Enterprise user submits ticket
        Sanctum::actingAs($enterpriseUser);
        $resEnt = $this->postJson('/api/portal/tickets', [
            'ticket_subject' => 'Production API latency inquiry',
            'ticket_message' => 'Latency is spiking above 200ms on our dedicated endpoint.',
            'urgency' => 'high',
        ]);

        $resEnt->assertStatus(201);
        $this->assertEquals(15, $enterpriseUser->fresh()->loyalty_points_balance);
        $entTicket = Ticket::latest('id')->first();

        // Algorithmic check: Enterprise ticket priority score must far exceed Standard
        $this->assertGreaterThan($stdTicket->priority_score, $entTicket->priority_score);
        $this->assertEquals('high', $entTicket->priority);
    }

    public function test_client_profile_completion_awards_points_only_once(): void
    {
        $user = $this->createClient(['loyalty_points_balance' => 0]);
        Sanctum::actingAs($user);

        // First completion
        $res1 = $this->postJson('/api/portal/profile/complete');
        $res1->assertStatus(200);
        $this->assertEquals(50, $user->fresh()->loyalty_points_balance);

        // Second completion (idempotent / no duplicate reward)
        $res2 = $this->postJson('/api/portal/profile/complete');
        $res2->assertStatus(200);
        $this->assertEquals(50, $user->fresh()->loyalty_points_balance);
    }

    public function test_client_can_submit_brief_and_earn_milestone_points(): void
    {
        $user = $this->createClient(['loyalty_points_balance' => 10]);
        $project = Project::create([
            'user_id' => $user->id,
            'project_name' => 'Logistics Automation Engine',
            'progress_stage' => 'planning',
            'progress_percentage' => 10,
        ]);

        Sanctum::actingAs($user);

        $res = $this->postJson("/api/portal/projects/{$project->id}/brief", [
            'brief_details' => 'Full automated dispatching system integrated with WhatsApp Bot.',
        ]);

        $res->assertStatus(200);
        $this->assertEquals(110, $user->fresh()->loyalty_points_balance);
        $this->assertTrue($project->fresh()->is_brief_complete);
    }

    public function test_client_can_redeem_loyalty_points_for_reward(): void
    {
        $user = $this->createClient(['loyalty_points_balance' => 500]);
        $reward = LoyaltyReward::create([
            'name' => '10% Billing Discount',
            'reward_type' => 'invoice_discount',
            'points_cost' => 300,
            'discount_value' => 10.00,
            'discount_type' => 'percentage',
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        // Success redemption
        $res = $this->postJson("/api/portal/loyalty/rewards/{$reward->id}/redeem");
        $res->assertStatus(200);
        $this->assertEquals(200, $user->fresh()->loyalty_points_balance);

        // Second attempt fails due to insufficient points (200 < 300)
        $resFail = $this->postJson("/api/portal/loyalty/rewards/{$reward->id}/redeem");
        $resFail->assertStatus(422);
        $this->assertEquals(200, $user->fresh()->loyalty_points_balance);
    }

    public function test_vault_access_is_strictly_authorized_to_owner(): void
    {
        Storage::fake('local');

        $owner = $this->createClient();
        $otherUser = $this->createClient();

        Storage::disk('local')->put('client_vault/code.zip', 'dummy source code content');

        $asset = ClientVaultAsset::create([
            'user_id' => $owner->id,
            'title' => 'Core Architecture Source Code.zip',
            'asset_type' => 'source_code',
            'storage_path' => 'client_vault/code.zip',
            'file_mime_type' => 'application/zip',
            'file_size_bytes' => 1024,
            'download_count' => 0,
        ]);

        // Unauthorized user attempts download
        Sanctum::actingAs($otherUser);
        $resUnauthorized = $this->get("/api/portal/vault/assets/{$asset->id}/download");
        $resUnauthorized->assertStatus(403);

        // Owner downloads
        Sanctum::actingAs($owner);
        $resOwner = $this->get("/api/portal/vault/assets/{$asset->id}/download");
        $resOwner->assertStatus(200);
        $this->assertEquals(1, $asset->fresh()->download_count);
    }
}
