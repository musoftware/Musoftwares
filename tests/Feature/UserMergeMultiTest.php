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
}
