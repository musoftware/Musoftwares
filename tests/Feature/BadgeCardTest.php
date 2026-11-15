<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\LoyaltyTier;
use App\Models\User;
use App\Models\UserReferral;
use App\Services\BadgeCardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BadgeCardTest extends TestCase
{
    use RefreshDatabase;

    private function clientWithPoints(int $points, array $extra = []): User
    {
        $currency = Currency::firstOrCreate(
            ['currency' => 'USD'],
            ['name' => 'US Dollar', 'symbol' => '$', 'string_format' => '%s%.2f']
        );

        return User::factory()->create(array_merge([
            'name' => 'Ahmed Samir Ali',
            'currency_id' => $currency->id,
            'loyalty_points_balance' => $points,
            'loyalty_lifetime_points' => $points,
        ], $extra));
    }

    private function goldTier(): LoyaltyTier
    {
        return LoyaltyTier::updateOrCreate(
            ['slug' => 'gold'],
            ['name' => 'Gold', 'min_lifetime_points' => 600, 'badge_color' => '#FFD700', 'order_index' => 3, 'is_active' => true]
        );
    }

    public function test_signed_link_shows_first_name_and_level_without_points(): void
    {
        $this->goldTier();
        $user = $this->clientWithPoints(750);

        $response = $this->get(app(BadgeCardService::class)->linkFor($user));

        $response->assertOk()
            ->assertSee('Ahmed')
            ->assertDontSee('Samir')
            ->assertDontSee('750')
            ->assertSee('images/tiers/gold.svg', false)
            ->assertSee('og:image', false);
    }

    public function test_link_without_signature_is_refused(): void
    {
        $user = $this->clientWithPoints(0);

        $this->get("/badge/{$user->id}")->assertForbidden();
    }

    public function test_card_uses_invite_link_and_safe_color(): void
    {
        $this->goldTier()->update(['badge_color' => 'red;}body{display:none']);
        $user = $this->clientWithPoints(700);
        UserReferral::create(['user_id' => $user->id, 'title' => 'Default', 'key' => 'abc123', 'slug' => 'ahmed-shop']);

        $card = app(BadgeCardService::class)->detailsFor($user);

        $this->assertSame('gold', $card['tier_slug']);
        $this->assertSame('#CD7F32', $card['color']);
        $this->assertSame(url('/r/ahmed-shop'), $card['invite_url']);
    }

    public function test_card_takes_the_higher_of_saved_and_earned_level(): void
    {
        $gold = $this->goldTier();
        $silver = LoyaltyTier::updateOrCreate(
            ['slug' => 'silver'],
            ['name' => 'Silver', 'min_lifetime_points' => 200, 'order_index' => 2, 'is_active' => true]
        );
        $user = $this->clientWithPoints(650, ['loyalty_tier_id' => $silver->id]);

        $this->assertSame('gold', app(BadgeCardService::class)->detailsFor($user)['tier_slug']);
        $this->assertNotNull($gold->id);
    }

    public function test_a_shared_card_shows_a_picture_with_the_name_and_level(): void
    {
        $this->goldTier();
        $user = $this->clientWithPoints(750, ['name' => 'محمود أمين']);
        $cards = app(BadgeCardService::class);

        $this->get($cards->linkFor($user))->assertOk()
            ->assertSee('<meta property="og:image" content="'.e($cards->imageLinkFor($user)).'">', false)
            ->assertSee('<meta name="twitter:card" content="summary_large_image">', false);

        $image = $this->get($cards->imageLinkFor($user))->assertOk()->assertHeader('Content-Type', 'image/png');
        $size = getimagesizefromstring($image->getContent());
        $this->assertSame([1200, 630], [$size[0], $size[1]]);

        $this->get("/badge/{$user->id}/image.png")->assertForbidden();
    }

    public function test_arabic_names_are_joined_for_the_picture(): void
    {
        // م ح م و د: initial, medial, medial, final waw; waw never joins the next letter, so dal stands alone; drawn right to left
        $this->assertSame('ﺩﻮﻤﺤﻣ', \App\Support\ArabicShaper::forImage('محمود'));
        $this->assertSame('ﻼﺳ', \App\Support\ArabicShaper::forImage('سلا'));
        $this->assertSame('Ahmed', \App\Support\ArabicShaper::forImage('Ahmed'));
        $this->assertSame('2023 ﻦﻣ', \App\Support\ArabicShaper::forImage('من 2023'));
    }
}
