<?php

namespace App\Services;

use App\Models\LoyaltyTier;
use App\Models\User;
use App\Models\UserReferral;
use Illuminate\Support\Str;

/**
 * The client's shareable badge card: a public link and the few details it shows.
 * It shows the first name and the level only, never points or money.
 */
class BadgeCardService
{
    private const DEFAULT_TIER = 'bronze';

    private const DEFAULT_COLOR = '#CD7F32';

    private const TOKEN_LENGTH = 16;

    /** A short link that works for anyone who has it: a random code, never the user id, so it shows nothing. */
    public function linkFor(User $user): string
    {
        return route('badge-card.show', ['token' => $this->tokenFor($user)]);
    }

    /** The shared picture of the card, under the same code. */
    public function imageLinkFor(User $user): string
    {
        return route('badge-card.image', ['token' => $this->tokenFor($user)]);
    }

    public function userFor(string $token): ?User
    {
        return User::where('badge_token', $token)->first();
    }

    /** The client's code, made the first time their card is shown. */
    private function tokenFor(User $user): string
    {
        if (! empty($user->badge_token)) {
            return $user->badge_token;
        }

        do {
            $token = Str::lower(Str::random(self::TOKEN_LENGTH));
        } while (User::where('badge_token', $token)->exists());
        $user->forceFill(['badge_token' => $token])->saveQuietly();

        return $token;
    }

    /**
     * @return array{first_name: string, tier_slug: string, tier_name: string, color: string, member_since: ?string, invite_url: string}
     */
    public function detailsFor(User $user): array
    {
        $tier = $this->tierFor($user);
        $slug = $this->badgeSlug($tier);

        return [
            'first_name' => $this->firstName($user),
            'tier_slug' => $slug,
            'tier_name' => $this->tierName($slug, $tier?->name),
            'color' => $this->safeColor($tier?->badge_color),
            'member_since' => $user->created_at?->format('Y'),
            'invite_url' => $this->inviteUrl($user),
        ];
    }

    /** The higher of the saved level and the level the points earn, same as the admin client page. */
    private function tierFor(User $user): ?LoyaltyTier
    {
        $points = max((int) $user->loyalty_lifetime_points, (int) $user->loyalty_points_balance);

        return collect([$user->loyaltyTier, LoyaltyTier::resolveForPoints($points)])
            ->filter()
            ->sortByDesc('min_lifetime_points')
            ->first();
    }

    /** A level without its own badge image falls back to the first level's badge. */
    private function badgeSlug(?LoyaltyTier $tier): string
    {
        $slug = strtolower($tier?->slug ?: self::DEFAULT_TIER);

        return is_file(public_path("images/tiers/{$slug}.svg")) ? $slug : self::DEFAULT_TIER;
    }

    /** The color goes into CSS and the image, so only a plain hex color is allowed. */
    private function safeColor(?string $color): string
    {
        return preg_match('/^#[0-9a-fA-F]{6}$/', (string) $color) ? $color : self::DEFAULT_COLOR;
    }

    private function tierName(string $slug, ?string $storedName): string
    {
        $key = "client_home.tiers.{$slug}";
        $text = __($key);

        return $text !== $key ? $text : ($storedName ?: $slug);
    }

    private function firstName(User $user): string
    {
        $name = trim((string) $user->name);

        return explode(' ', $name)[0] ?: $name;
    }

    /** Sharing the card also shares the client's invite link, so new sign-ups earn them points. */
    private function inviteUrl(User $user): string
    {
        $referral = UserReferral::where('user_id', $user->id)->first();
        $code = $referral?->slug ?: $referral?->key;

        return $code ? url('/r/'.$code) : url('/');
    }
}
