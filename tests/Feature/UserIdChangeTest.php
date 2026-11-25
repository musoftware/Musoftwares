<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\Invoice;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserIdChangeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->admin = User::factory()->create(['onboarding_completed' => true]);
        $this->admin->assignRole('admin');
    }

    public function test_the_account_and_everything_it_owns_move_to_a_free_id(): void
    {
        $currency = Currency::firstOrCreate(['currency' => 'USD'], ['name' => 'US Dollar', 'symbol' => '$', 'string_format' => '%s%.2f']);
        $client = User::factory()->create(['currency_id' => $currency->id]);
        $client->assignRole('client');
        $invoice = Invoice::create(['user_id' => $client->id, 'currency_id' => $currency->id, 'paid' => 0, 'status' => 'unpaid', 'is_suspended' => false, 'due_date' => now()->addWeek()]);
        $client->createToken('phone');
        // an account this one brought in points at it from the users table itself
        $referred = User::factory()->create(['ref_user_id' => $client->id]);
        $oldId = $client->id;
        $newId = 5000;

        $this->actingAs($this->admin)->get("/admin/users/{$oldId}/change-id?to={$newId}")->assertInertia(fn ($page) => $page
            ->where('free', true)
            ->where('moves', fn ($moves) => collect($moves)->contains(fn ($move) => $move['table'] === 'invoices' && $move['rows'] === 1)));

        $this->actingAs($this->admin)->post("/admin/users/{$oldId}/change-id", ['new_id' => $newId])->assertRedirect("/admin/users/{$newId}");

        $moved = User::findOrFail($newId);
        $this->assertNull(User::withTrashed()->find($oldId));
        $this->assertSame($client->email, $moved->email);
        // the rows that pointed at the account point at its new ID: a plain column, a role and a token (polymorphic)
        $this->assertSame($newId, (int) $invoice->fresh()->user_id);
        $this->assertTrue($moved->hasRole('client'));
        $this->assertSame(1, $moved->tokens()->count());
        $this->assertSame($newId, (int) $referred->fresh()->ref_user_id);
        $this->assertSame(0, DB::table('model_has_roles')->where('model_id', $oldId)->where('model_type', User::class)->count());
        $this->assertDatabaseHas('admin_audit_logs', ['action' => 'users.id_changed', 'target_id' => $newId]);
    }

    public function test_an_id_that_is_used_or_was_used_is_refused(): void
    {
        $client = User::factory()->create();
        $deleted = User::factory()->create();
        $deleted->delete();

        foreach ([$this->admin->id, $deleted->id] as $taken) {
            $this->actingAs($this->admin)->post("/admin/users/{$client->id}/change-id", ['new_id' => $taken])->assertSessionHasErrors('new_id');
        }
        $this->assertNotNull($client->fresh());
    }

    /** A merge kept the newer ID (912) and soft deleted the old copy (1): the account takes 1 back. */
    public function test_an_account_takes_back_the_id_of_its_merged_copy(): void
    {
        $currency = Currency::firstOrCreate(['currency' => 'USD'], ['name' => 'US Dollar', 'symbol' => '$', 'string_format' => '%s%.2f']);
        $copy = User::factory()->create();
        $account = User::factory()->create();
        $copy->forceFill(['merged_into_user_id' => $account->id])->save();
        $copy->delete();
        $leftOnCopy = Invoice::create(['user_id' => $copy->id, 'currency_id' => $currency->id, 'paid' => 0, 'status' => 'unpaid', 'is_suspended' => false, 'due_date' => now()]);
        $owned = Invoice::create(['user_id' => $account->id, 'currency_id' => $currency->id, 'paid' => 0, 'status' => 'unpaid', 'is_suspended' => false, 'due_date' => now()]);
        $oldId = (int) $copy->id;

        $this->actingAs($this->admin)->get("/admin/users/{$account->id}/change-id?to={$oldId}")->assertInertia(fn ($page) => $page->where('free', true));
        $this->actingAs($this->admin)->post("/admin/users/{$account->id}/change-id", ['new_id' => $oldId])->assertRedirect("/admin/users/{$oldId}");

        $this->assertSame($account->email, User::findOrFail($oldId)->email);
        $this->assertSame($oldId, (int) $owned->fresh()->user_id);
        // the copy is kept (still soft deleted) at a new ID, with what was left on it, and still points at the account
        $movedCopy = User::onlyTrashed()->where('email', $copy->email)->sole();
        $this->assertNotSame($oldId, (int) $movedCopy->id);
        $this->assertSame($oldId, (int) $movedCopy->merged_into_user_id);
        $this->assertSame((int) $movedCopy->id, (int) $leftOnCopy->fresh()->user_id);
    }

    public function test_the_id_of_a_copy_merged_into_another_account_is_refused(): void
    {
        $copy = User::factory()->create(['merged_into_user_id' => $this->admin->id]);
        $copy->delete();
        $client = User::factory()->create();

        $this->actingAs($this->admin)->post("/admin/users/{$client->id}/change-id", ['new_id' => $copy->id])->assertSessionHasErrors('new_id');
    }

    public function test_the_admin_who_changes_their_own_id_stays_signed_in(): void
    {
        $this->actingAs($this->admin)->post("/admin/users/{$this->admin->id}/change-id", ['new_id' => 7000])->assertRedirect('/admin/users/7000');

        $this->assertSame(7000, (int) auth()->id());
        $this->assertTrue(User::find(7000)->hasRole('admin'));
    }
}
