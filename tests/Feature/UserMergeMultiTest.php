<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserEmail;
use App\Services\UserMergeService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserMergeMultiTest extends TestCase
{
    use RefreshDatabase;

    private UserMergeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->service = app(UserMergeService::class);
    }

    public function test_add_alias_from_duplicate_promotes_email(): void
    {
        $survivor = User::factory()->create(['email' => 'primary@example.com']);
        $dup = User::factory()->create(['email' => 'secondary@example.com']);

        $added = $this->service->addAliasFromDuplicate($dup, $survivor);

        $this->assertTrue($added);
        $this->assertDatabaseHas('user_emails', [
            'user_id' => $survivor->id,
            'email' => 'secondary@example.com',
            'source' => UserEmail::SOURCE_MERGE,
        ]);
        $alias = UserEmail::where('user_id', $survivor->id)->first();
        $this->assertNotNull($alias->verified_at);
    }

    public function test_add_alias_uses_lowercased_email_and_source_merge(): void
    {
        $survivor = User::factory()->create(['email' => 's@example.com']);
        $dup = User::factory()->create(['email' => 'MIXED@Example.COM']);

        $this->service->addAliasFromDuplicate($dup, $survivor);

        $alias = UserEmail::where('user_id', $survivor->id)->first();
        $this->assertSame('mixed@example.com', $alias->email);
        $this->assertSame(UserEmail::SOURCE_MERGE, $alias->source);
        $this->assertNotNull($alias->verified_at);
    }

    public function test_add_alias_skips_when_collision_with_existing_alias(): void
    {
        $survivor = User::factory()->create(['email' => 's@example.com']);
        $dup = User::factory()->create(['email' => 'a@example.com']);
        UserEmail::create([
            'user_id' => $survivor->id,
            'email' => 'a@example.com',
            'verified_at' => now(),
            'source' => UserEmail::SOURCE_ADMIN,
        ]);

        $added = $this->service->addAliasFromDuplicate($dup, $survivor);

        $this->assertFalse($added);
        $this->assertSame(1, UserEmail::where('user_id', $survivor->id)->count());
    }

    public function test_merge_many_rejects_self_merge_in_batch(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->service->mergeMany(123, [123, 999], [], 0);
    }

    public function test_merge_many_accepts_empty_array_by_throwing(): void
    {
        $survivor = User::factory()->create(['email' => 's@example.com']);
        $this->expectException(\RuntimeException::class);
        $this->service->mergeMany($survivor->id, [], [], 0);
    }

    public function test_merge_many_elects_oldest_active_id_across_batch(): void
    {
        $oldest = User::factory()->create(['email' => 'oldest@example.com']);
        $middle = User::factory()->create(['email' => 'middle@example.com']);
        $newest = User::factory()->create(['email' => 'newest@example.com']);

        $outcomes = $this->service->mergeMany($newest->id, [$middle->id, $oldest->id], [], 0);

        $this->assertCount(2, $outcomes);
        $this->assertNull($oldest->fresh()->deleted_at);
        $this->assertSame($oldest->id, User::withTrashed()->find($middle->id)->merged_into_user_id);
        $this->assertSame($oldest->id, User::withTrashed()->find($newest->id)->merged_into_user_id);
    }

    public function test_merge_many_does_not_elect_trashed_older_user_as_survivor(): void
    {
        $trashedOldest = User::factory()->create([
            'email' => 'trashed-old@example.com',
            'deleted_at' => now(),
        ]);
        $activeOlder = User::factory()->create(['email' => 'active-older@example.com']);
        $activeNewer = User::factory()->create(['email' => 'active-newer@example.com']);

        $outcomes = $this->service->mergeMany($activeNewer->id, [$trashedOldest->id, $activeOlder->id], [], 0);

        $this->assertNull($activeOlder->fresh()->deleted_at);
        $this->assertSame($activeOlder->id, User::withTrashed()->find($activeNewer->id)->merged_into_user_id);

        $byDup = collect($outcomes)->keyBy('duplicate_id');
        $this->assertSame('skipped', $byDup[$trashedOldest->id]['status']);
        $this->assertSame('merged', $byDup[$activeNewer->id]['status']);
    }
}
