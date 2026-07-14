<?php

namespace Tests\Feature\Admin;

use App\Models\Currency;
use App\Models\PaymentLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PaymentLinkControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'super_admin']);
        Role::firstOrCreate(['name' => 'accountant']);
        Role::firstOrCreate(['name' => 'client']);

        if (! Currency::query()->exists()) {
            Currency::create(['currency' => 'USD', 'symbol' => '$', 'string_format' => '$%s']);
        }
    }

    private function makeUser(array $roles = ['admin']): User
    {
        $user = User::factory()->create(['onboarding_completed' => true]);
        $user->markEmailAsVerified();
        foreach ($roles as $role) {
            $user->assignRole($role);
        }

        return $user;
    }

    public function test_index_renders_with_pagination_and_stats(): void
    {
        $user = $this->makeUser();
        PaymentLink::factory()->count(3)->create(['user_id' => $user->id]);
        PaymentLink::factory()->paid()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('admin.payment-links.index'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Finance/PaymentLinks/Index')
                ->has('paymentLinks.data', 4)
                ->has('stats', fn ($s) => $s
                    ->where('total', 4)
                    ->where('paid', 1)
                    ->where('pending', 3)
                    ->etc()
                )
            );
    }

    public function test_store_persists_new_link(): void
    {
        $user = $this->makeUser();
        $currency = Currency::query()->first();

        $this->actingAs($user)->post(route('admin.payment-links.store'), [
            'title' => 'Website project',
            'description' => 'Phase 1',
            'amount' => 250.50,
            'currency_id' => $currency->id,
            'expires_at' => now()->addDays(7)->format('Y-m-d H:i:s'),
        ])->assertRedirect();

        $this->assertDatabaseHas('payment_links', [
            'user_id' => $user->id,
            'title' => 'Website project',
            'amount' => 250.50,
            'status' => PaymentLink::STATUS_PENDING,
        ]);
    }

    public function test_update_modifies_pending_link(): void
    {
        $user = $this->makeUser();
        $link = PaymentLink::factory()->create(['user_id' => $user->id, 'amount' => 100]);
        $currency = Currency::query()->first();

        $this->actingAs($user)->put(route('admin.payment-links.update', $link), [
            'title' => 'Updated',
            'amount' => 200,
            'currency_id' => $currency->id,
        ])->assertRedirect();

        $link->refresh();
        $this->assertSame('Updated', $link->title);
        $this->assertSame('200.00', (string) $link->amount);
    }

    public function test_update_rejects_paid_link(): void
    {
        $user = $this->makeUser();
        $link = PaymentLink::factory()->paid()->create(['user_id' => $user->id]);
        $currency = Currency::query()->first();

        $this->actingAs($user)->put(route('admin.payment-links.update', $link), [
            'title' => 'Nope',
            'amount' => 1,
            'currency_id' => $currency->id,
        ])->assertRedirect();

        $link->refresh();
        $this->assertSame(PaymentLink::STATUS_PAID, $link->status);
        $this->assertNotSame('Nope', $link->title);
    }

    public function test_cancel_marks_pending_as_cancelled(): void
    {
        $user = $this->makeUser();
        $link = PaymentLink::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->put(route('admin.payment-links.cancel', $link))
            ->assertRedirect();

        $link->refresh();
        $this->assertSame(PaymentLink::STATUS_CANCELLED, $link->status);
        $this->assertNotNull($link->cancelled_at);
    }

    public function test_mark_paid_requires_super_admin(): void
    {
        $adminOnly = $this->makeUser(['admin']);
        $linkAdmin = PaymentLink::factory()->create(['user_id' => $adminOnly->id]);
        $this->actingAs($adminOnly)
            ->post(route('admin.payment-links.mark-paid', $linkAdmin))
            ->assertStatus(403);

        $super = $this->makeUser(['super_admin']);
        $link2 = PaymentLink::factory()->create(['user_id' => $super->id]);

        $this->actingAs($super)
            ->post(route('admin.payment-links.mark-paid', $link2))
            ->assertRedirect();

        $link2->refresh();
        $this->assertSame(PaymentLink::STATUS_PAID, $link2->status);
        $this->assertSame(PaymentLink::METHOD_MANUAL, $link2->paid_method);
        $this->assertSame($super->id, ($link2->metadata ?? [])['marked_paid_by_user_id'] ?? null);
    }

    public function test_destroy_soft_deletes(): void
    {
        $user = $this->makeUser();
        $link = PaymentLink::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->delete(route('admin.payment-links.destroy', $link))->assertRedirect();

        $this->assertSoftDeleted('payment_links', ['id' => $link->id]);
    }

    public function test_tenant_scopes_index_to_owner_for_non_admin(): void
    {
        $owner = $this->makeUser(['accountant']);
        $other = $this->makeUser(['accountant']);
        PaymentLink::factory()->count(2)->create(['user_id' => $owner->id]);
        PaymentLink::factory()->count(3)->create(['user_id' => $other->id]);

        $this->actingAs($owner)
            ->get(route('admin.payment-links.index'))
            ->assertInertia(fn ($page) => $page
                ->has('paymentLinks.data', 2)
                ->where('stats.total', 2)
            );
    }

    public function test_bulk_destroy_removes_only_owned_links_for_non_admin(): void
    {
        $user = $this->makeUser(['accountant']);
        $a = PaymentLink::factory()->create(['user_id' => $user->id]);
        $b = PaymentLink::factory()->create(['user_id' => $user->id]);
        $other = PaymentLink::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.payment-links.bulk-destroy'), ['ids' => [$a->id, $b->id, $other->id]])
            ->assertRedirect();

        $this->assertSoftDeleted('payment_links', ['id' => $a->id]);
        $this->assertSoftDeleted('payment_links', ['id' => $b->id]);
        $this->assertNull($other->fresh()->deleted_at);
    }
}