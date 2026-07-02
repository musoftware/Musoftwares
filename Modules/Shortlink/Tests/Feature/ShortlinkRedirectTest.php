<?php

namespace Modules\Shortlink\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Shortlink\Models\ShortlinkLink;
use Modules\Shortlink\Services\ShortlinkService;
use Tests\TestCase;

class ShortlinkRedirectTest extends TestCase
{
    use RefreshDatabase;

    private function makeLink(array $overrides = []): ShortlinkLink
    {
        return ShortlinkLink::create(array_merge([
            'short_code' => Str::random(10),
            'destination_url' => 'https://example.com/destination?signed=abc',
            'created_by_user_id' => null,
            'is_active' => true,
            'clicks' => 0,
        ], $overrides));
    }

    public function test_redirect_returns_302_to_destination(): void
    {
        $link = $this->makeLink(['destination_url' => 'https://example.com/target']);

        $response = $this->get('/l/' . $link->short_code);

        $response->assertRedirect('https://example.com/target');
        $response->assertStatus(302);
    }

    public function test_redirect_sets_no_cache_headers(): void
    {
        $link = $this->makeLink();

        $response = $this->get('/l/' . $link->short_code);

        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_unknown_code_returns_404(): void
    {
        $this->get('/l/DOESNOTEXIST')->assertNotFound();
    }

    public function test_expired_link_returns_410(): void
    {
        $link = $this->makeLink(['expires_at' => now()->subDay()]);

        $this->get('/l/' . $link->short_code)->assertStatus(410);
    }

    public function test_inactive_link_returns_410(): void
    {
        $link = $this->makeLink(['is_active' => false]);

        $this->get('/l/' . $link->short_code)->assertStatus(410);
    }

    public function test_soft_deleted_link_returns_410(): void
    {
        $link = $this->makeLink();
        $link->delete();

        $this->get('/l/' . $link->short_code)->assertStatus(410);
    }

    public function test_click_is_incremented_atomically_per_redirect(): void
    {
        $link = $this->makeLink(['clicks' => 0]);

        $this->get('/l/' . $link->short_code);
        $this->get('/l/' . $link->short_code);

        $this->assertSame(2, (int) ShortlinkLink::whereKey($link->id)->value('clicks'));
    }

    public function test_generated_codes_match_base62_length_policy(): void
    {
        $service = app(ShortlinkService::class);

        for ($i = 0; $i < 20; $i++) {
            $code = $service->generateUniqueCode();
            $this->assertSame(ShortlinkService::CODE_LENGTH, strlen($code));
            $this->assertMatchesRegularExpression('/^[0-9a-zA-Z]{10}$/', $code);
        }
    }

    public function test_create_produces_unique_codes_for_many_links(): void
    {
        $service = app(ShortlinkService::class);
        $codes = [];

        for ($i = 0; $i < 50; $i++) {
            $codes[] = $service->create(['destination_url' => "https://example.com/{$i}"])->short_code;
        }

        $this->assertSame(50, count(array_unique($codes)));
    }

    public function test_collision_retry_produces_a_unique_code(): void
    {
        // Seed an existing code that the generator will collide with first.
        $existingCode = 'AAAAAAAAAA';
        $this->makeLink(['short_code' => $existingCode]);

        $service = \Mockery::mock(ShortlinkService::class)->makePartial();
        // First attempt collides, the second attempt yields a fresh unique code.
        $service->shouldReceive('generateUniqueCode')
            ->andReturn($existingCode, 'ZZZZZZZZZZ');

        $link = $service->create(['destination_url' => 'https://example.com/collision']);

        $this->assertSame('ZZZZZZZZZZ', $link->short_code);
        $this->assertDatabaseHas('shortlink_links', ['short_code' => 'ZZZZZZZZZZ']);

        \Mockery::close();
    }

    public function test_service_resolves_only_active_non_expired_links(): void
    {
        $service = app(ShortlinkService::class);

        $active = $this->makeLink();
        $inactive = $this->makeLink(['is_active' => false]);
        $expired = $this->makeLink(['expires_at' => now()->subHour()]);

        $this->assertNotNull($service->resolve($active->short_code));
        $this->assertNull($service->resolve($inactive->short_code));
        $this->assertNull($service->resolve($expired->short_code));
    }

    public function test_find_or_create_dedupes_by_destination(): void
    {
        $service = app(ShortlinkService::class);
        $url = 'https://example.com/shared-board/' . Str::random(6);

        $first = $service->findOrCreateForDestination($url);
        $second = $service->findOrCreateForDestination($url);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, ShortlinkLink::where('destination_url', $url)->count());
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}
