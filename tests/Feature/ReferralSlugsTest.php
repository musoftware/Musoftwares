<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserReferral;
use App\Services\ReferralSlugs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferralSlugsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_link_name_is_the_first_name_then_a_little_of_the_email(): void
    {
        $slugs = app(ReferralSlugs::class);

        $first = $this->referral('Ahmed Samir Ali', 'samir.ali@example.com');
        $this->assertSame('ahmed', $slugs->ensure($first));

        // the family name never shows; a second Ahmed gets part of his email
        $second = $this->referral('Ahmed Hassan', 'hassan77@example.com');
        $this->assertSame('ahmed-has', $slugs->ensure($second));

        $arabic = $this->referral('محمود أمين', 'm.amin@example.com');
        $this->assertMatchesRegularExpression(ReferralSlugs::PATTERN, $slugs->ensure($arabic));
        $this->assertStringNotContainsString('amin', $arabic->slug);
    }

    public function test_a_client_changes_the_name_and_every_old_link_still_reaches_them(): void
    {
        $referral = $this->referral('Ahmed Samir', 'samir@example.com');
        app(ReferralSlugs::class)->ensure($referral);
        $this->actingAs($referral->user);

        $this->post(route('referrals.update_slug'), ['slug' => 'ahmed-store'])->assertSessionHasNoErrors();
        $this->post(route('referrals.update_slug'), ['slug' => 'Ahmed-Shop'])->assertSessionHasNoErrors();
        $this->assertSame('ahmed-shop', $referral->refresh()->slug);

        foreach (['ahmed', 'ahmed-store', 'ahmed-shop', $referral->key] as $old) {
            $this->assertSame($referral->id, UserReferral::resolveRef($old)?->id, $old);
        }

        // nobody else can take a name that still points to this client
        $other = $this->referral('Mona Adel', 'mona@example.com');
        $this->actingAs($other->user)->post(route('referrals.update_slug'), ['slug' => 'ahmed-store'])->assertSessionHasErrors('slug');
        $this->post(route('referrals.update_slug'), ['slug' => 'bad name!'])->assertSessionHasErrors('slug');

        // the client can go back to a name they had
        $this->actingAs($referral->user)->post(route('referrals.update_slug'), ['slug' => 'ahmed'])->assertSessionHasNoErrors();
        $this->assertSame('ahmed', $referral->refresh()->slug);
        $this->assertSame($referral->id, UserReferral::resolveRef('ahmed-shop')?->id);
    }

    private function referral(string $name, string $email): UserReferral
    {
        $user = User::factory()->create(['name' => $name, 'email' => $email, 'onboarding_completed' => true]);

        return UserReferral::create(['user_id' => $user->id, 'title' => 'Primary Campaign', 'key' => sha1($email)])->load('user');
    }
}
