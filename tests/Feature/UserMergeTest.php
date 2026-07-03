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
            'currency'=> 1,
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
            'currency'=> 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->service->merge($survivor->id, $duplicate->id, ['name' => 'survivor'], 0);

        $this->assertDatabaseHas('transactions', ['id' => $txnId, 'user_id' => $survivor->id]);
        $this->assertNull($duplicate->fresh());

        $trashed = User::withTrashed()->find($duplicate->id);
        $this->assertNotNull($trashed->deleted_at);
        $this->assertSame($survivor->id, $trashed->merged_into_user_id);

        $this->assertDatabaseHas('admin_audit_logs', [
            'action'      => 'users.merged',
            'actor_user_id' => 0,
            'target_id'   => $duplicate->id,
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
            'slug' => 'shared-' . uniqid(),
            'currency' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        Schema::table('projects', function ($table) {
            $table->unique(['project_name', 'user_id']);
        });

        DB::table('projects')->insert([
            'user_id' => $duplicate->id,
            'project_name' => 'Shared',
            'slug' => 'shared-dup-' . uniqid(),
            'currency' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->service->merge($survivor->id, $duplicate->id, [], 0);

        $this->assertNotNull(User::withTrashed()->find($duplicate->id)->deleted_at);
    }

    public function test_repeat_merge_on_already_merged_duplicate_is_rejected(): void
    {
        $survivor  = User::factory()->create();
        $duplicate = User::factory()->create();

        $this->service->merge($survivor->id, $duplicate->id, [], 0);

        $this->expectException(\RuntimeException::class);
        $this->service->merge($survivor->id, $duplicate->id, [], 0);
    }

    public function test_command_dry_run_does_not_merge(): void
    {
        $survivor  = User::factory()->create();
        $duplicate = User::factory()->create();

        $exit = Artisan::call('users:merge', [
            'survivor'  => $survivor->id,
            'duplicate' => $duplicate->id,
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
            'user_id' => $duplicate->id, 'amount' => 1.0, 'type' => 'received', 'currency' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->service->merge($survivor->id, $duplicate->id, ['name' => 'survivor'], 0);

        $log = AdminAuditLog::where('action', 'users.merged')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame($survivor->id, $log->meta['survivor_id']);
        $this->assertSame($duplicate->id, $log->meta['duplicate_id']);
        $this->assertSame(0, $log->meta['tokens_revoked']);
        $this->assertNotEmpty($log->meta['snapshot']['reassignments']);
    }

    public function test_merge_rejects_same_id(): void
    {
        $u = User::factory()->create();
        $this->expectException(\RuntimeException::class);
        $this->service->merge($u->id, $u->id, [], 0);
    }
}
