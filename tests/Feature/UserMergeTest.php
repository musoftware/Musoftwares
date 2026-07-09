<?php

namespace Tests\Feature;

use App\Models\AdminAuditLog;
use App\Models\User;
use App\Services\UserMergeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserMergeTest extends TestCase
{
    use RefreshDatabase;

    private UserMergeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->service = app(UserMergeService::class);
    }

    public function test_preview_reports_conflicts_and_counts_but_writes_nothing(): void
    {
        $survivor  = User::factory()->create(['name' => 'Hossam', 'email' => 'a@example.com']);
        $duplicate = User::factory()->create(['name' => 'Hossam S', 'email' => 'b@example.com']);

        DB::table('transactions')->insert([
            'user_id' => $duplicate->id,
            'amount'  => 100.0,
            'type'    => 'received',
            'currency_id'=> 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $snapshot = [
            'users'     => User::count(),
            'txns'      => DB::table('transactions')->count(),
            'dupe_name' => $duplicate->fresh()->name,
        ];

        $preview = $this->service->preview($survivor->id, $duplicate->id);

        $this->assertSame($preview['survivor']['id'], $survivor->id);
        $this->assertSame($preview['duplicate']['id'], $duplicate->id);
        $this->assertArrayHasKey('name', $preview['field_conflicts']);
        $this->assertSame(1, $preview['child_counts']['transactions.user_id'] ?? null);

        $this->assertSame($snapshot['users'], User::count());
        $this->assertSame($snapshot['txns'], DB::table('transactions')->count());
        $this->assertSame($snapshot['dupe_name'], $duplicate->fresh()->name);
    }

    public function test_merge_reassigns_child_rows_and_soft_deletes_duplicate(): void
    {
        $survivor  = User::factory()->create(['name' => 'S', 'email' => 's@example.com']);
        $duplicate = User::factory()->create(['name' => 'D', 'email' => 'd@example.com']);

        $txnId = DB::table('transactions')->insertGetId([
            'user_id' => $duplicate->id,
            'amount'  => 50.0,
            'type'    => 'received',
            'currency_id'=> 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->service->merge($survivor->id, $duplicate->id, ['name' => 'survivor'], 0);

        $this->assertDatabaseHas('transactions', ['id' => $txnId, 'user_id' => $survivor->id]);

        $trashed = User::withTrashed()->find($duplicate->id);
        $this->assertNotNull($trashed->deleted_at);
        $this->assertSame($survivor->id, $trashed->merged_into_user_id);

        $this->assertDatabaseHas('admin_audit_logs', [
            'action'      => 'users.merged',
            'actor_user_id' => 0,
            'target_id'   => $survivor->id,
        ]);
    }

    public function test_merge_skips_unique_collisions_without_throwing(): void
    {
        $survivor  = User::factory()->create(['name' => 'S', 'email' => 's@example.com']);
        $duplicate = User::factory()->create(['name' => 'D', 'email' => 'd@example.com']);

        // If a (project_id, user_id) UNIQUE exists, rows must be skipped, not crashing the merge.
        $projectId = DB::table('projects')->insertGetId([
            'user_id' => $survivor->id,
            'project_name' => 'Shared',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        Schema::table('projects', function ($table) {
            $table->unique(['project_name', 'user_id']);
        });

        DB::table('projects')->insert([
            'user_id' => $duplicate->id,
            'project_name' => 'Shared',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->service->merge($survivor->id, $duplicate->id, [], 0);

        $this->assertNotNull(User::withTrashed()->find($duplicate->id)->deleted_at);
    }

    public function test_repeat_merge_on_already_merged_duplicate_is_rejected(): void
    {
        $survivor  = User::factory()->create();
        $duplicate = User::factory()->create();

        $outcomes = $this->service->mergeMany($survivor->id, [$duplicate->id], [], 0);
        $this->assertSame('merged', $outcomes[0]['status']);

        $outcomes2 = $this->service->mergeMany($survivor->id, [$duplicate->id], [], 0);
        $this->assertSame('skipped', $outcomes2[0]['status']);
    }

    public function test_command_dry_run_does_not_merge(): void
    {
        $survivor  = User::factory()->create();
        $duplicate = User::factory()->create();

        $exit = Artisan::call('users:merge', [
            'survivor'  => $survivor->id,
            'duplicates' => [$duplicate->id],
            '--dry-run' => true,
        ]);

        $this->assertSame(0, $exit);
        $this->assertNotNull($duplicate->fresh());
        $this->assertNull($duplicate->deleted_at);
    }

    public function test_merge_writes_audit_log_with_resolutions_and_counts(): void
    {
        $survivor  = User::factory()->create(['name' => 'A', 'email' => 'a@x.com']);
        $duplicate = User::factory()->create(['name' => 'B', 'email' => 'b@x.com']);

        DB::table('transactions')->insert([
            'user_id' => $duplicate->id, 'amount' => 1.0, 'type' => 'received', 'currency_id' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->service->merge($survivor->id, $duplicate->id, ['name' => 'survivor'], 0);

        $log = AdminAuditLog::where('action', 'users.merged')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame($survivor->id, $log->meta['survivor_id']);
        $this->assertSame($duplicate->id, $log->meta['duplicate_id']);
        $this->assertSame(0, $log->meta['batch_snapshot']['tokens_revoked']);
        $this->assertNotEmpty($log->meta['batch_snapshot']['reassignments']);
    }

    public function test_merge_rejects_same_id(): void
    {
        $u = User::factory()->create();
        $this->expectException(\RuntimeException::class);
        $this->service->merge($u->id, $u->id, [], 0);
    }

    public function test_select_page_excludes_survivor_and_soft_deleted(): void
    {
        $admin    = User::factory()->create(['onboarding_completed' => true]);
        $admin->assignRole('admin');
        $survivor = User::factory()->create(['name' => 'Alice', 'email' => 'alice@example.com']);
        $match    = User::factory()->create(['name' => 'Alicia', 'email' => 'alicia@example.com']);
        $deleted  = User::factory()->create(['name' => 'Alick', 'email' => 'alick@example.com', 'deleted_at' => now()]);
        $other    = User::factory()->create(['name' => 'Bob', 'email' => 'bob@example.com']);

        $response = $this->actingAs($admin)->get(
            route('admin.users.merge.select', $survivor->id, false) . '?search=ali'
        );

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Users/MergeSelect')
            ->where('survivor.id', $survivor->id)
            ->where('search', 'ali')
            ->has('suggestions', 1)
            ->where('suggestions.0.id', $match->id)
        );
    }

    public function test_select_page_with_no_search_returns_empty_suggestions(): void
    {
        $admin    = User::factory()->create(['onboarding_completed' => true]);
        $admin->assignRole('admin');
        $survivor = User::factory()->create();

        $response = $this->actingAs($admin)->get(
            route('admin.users.merge.select', $survivor->id, false)
        );

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Users/MergeSelect')
            ->where('search', '')
            ->has('suggestions', 0)
        );
    }

    public function test_select_page_includes_recently_merged(): void
    {
        $admin    = User::factory()->create(['onboarding_completed' => true]);
        $admin->assignRole('admin');
        $survivor = User::factory()->create();
        $merged   = User::factory()->create([
            'name'               => 'Old Dup',
            'email'              => 'old@example.com',
            'deleted_at'         => now(),
            'merged_into_user_id'=> $survivor->id,
        ]);

        $response = $this->actingAs($admin)->get(
            route('admin.users.merge.select', $survivor->id, false)
        );

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('recently_merged.0.id', $merged->id)
            ->where('recently_merged.0.email', 'old@example.com')
        );
    }

    public function test_preview_route_loads_successfully_for_admin(): void
    {
        $admin    = User::factory()->create(['onboarding_completed' => true]);
        $admin->assignRole('admin');
        $survivor = User::factory()->create();
        $duplicate = User::factory()->create();

        $response = $this->actingAs($admin)->get(
            route('admin.users.merge.preview', $survivor->id) . '?duplicate_ids[]=' . $duplicate->id
        );

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Users/Merge')
            ->where('survivor.id', $survivor->id)
            ->has('duplicates', 1)
            ->where('duplicates.0.id', $duplicate->id)
        );
    }

    public function test_confirm_route_successfully_merges_users_and_redirects(): void
    {
        $admin    = User::factory()->create(['onboarding_completed' => true]);
        $admin->assignRole('admin');
        $survivor = User::factory()->create();
        $duplicate = User::factory()->create();

        $txnId = DB::table('transactions')->insertGetId([
            'user_id' => $duplicate->id,
            'amount'  => 50.0,
            'type'    => 'received',
            'currency_id'=> 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // Notice we do NOT pass survivor_id in the request payload, matching the frontend behavior.
        $response = $this->actingAs($admin)->post(
            route('admin.users.merge.confirm', $survivor->id),
            [
                'duplicate_ids' => [$duplicate->id],
                'resolutions' => [],
            ]
        );

        $response->assertRedirect(route('admin.users.show', $survivor->id));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('transactions', ['id' => $txnId, 'user_id' => $survivor->id]);
        
        $trashed = User::withTrashed()->find($duplicate->id);
        $this->assertNotNull($trashed->deleted_at);
        $this->assertSame($survivor->id, $trashed->merged_into_user_id);
    }

    public function test_confirm_route_unauthorized_for_non_admins(): void
    {
        $client   = User::factory()->create(['onboarding_completed' => true]);
        $client->assignRole('client');
        $survivor = User::factory()->create();
        $duplicate = User::factory()->create();

        $response = $this->actingAs($client)->post(
            route('admin.users.merge.confirm', $survivor->id),
            [
                'duplicate_ids' => [$duplicate->id],
                'resolutions' => [],
            ]
        );

        $response->assertForbidden();
    }
}

