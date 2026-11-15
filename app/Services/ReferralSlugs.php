<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\UserReferral;
use App\Models\UserReferralSlug;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The name at the end of an invite link (/r/ahmed). It is made from the first name only, never the full name;
 * when that is taken, a little of the email is added, then a number. The client can change it, and every name
 * they had before keeps working, like the long code each link started with.
 */
final class ReferralSlugs
{
    public const PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    private const MAX_NAME = 20;

    /** The referral's name, made now if it has none. */
    public function ensure(UserReferral $referral): string
    {
        if (! empty($referral->slug)) {
            return $referral->slug;
        }

        $referral->slug = $this->suggest($referral->user ?? new User);
        $referral->saveQuietly();

        return $referral->slug;
    }

    public function suggest(User $user): string
    {
        $first = $this->part(Str::before(trim((string) $user->name), ' '), self::MAX_NAME);
        $email = $this->part(Str::before((string) $user->email, '@'), 12);
        $base = $first !== '' ? $first : ($email !== '' ? $email : 'friend');

        $tries = array_filter([$base, $first !== '' && $email !== '' ? $base.'-'.substr($email, 0, 3) : null, $first !== '' && $email !== '' ? $base.'-'.substr($email, 0, 6) : null]);
        foreach ($tries as $try) {
            if (strlen($try) >= 2 && $this->isFree($try)) {
                return $try;
            }
        }

        do {
            $try = $base.'-'.random_int(10, 9999);
        } while (! $this->isFree($try));

        return $try;
    }

    /** Free when no link uses it now or used it before; the referral's own old names are free for it. */
    public function isFree(string $slug, ?UserReferral $for = null): bool
    {
        $current = UserReferral::withTrashed()->where('slug', $slug)->when($for, fn ($q) => $q->whereKeyNot($for->id))->exists();
        $old = UserReferralSlug::withTrashed()->where('slug', $slug)->when($for, fn ($q) => $q->where('user_referral_id', '!=', $for->id))->exists();

        return ! $current && ! $old && ! UserReferral::withTrashed()->where('key', $slug)->exists();
    }

    /** The client chose a new name: the one they had keeps working and points to them. */
    public function change(UserReferral $referral, string $slug): void
    {
        DB::transaction(function () use ($referral, $slug): void {
            $previous = (string) $referral->slug;
            if ($previous !== '' && $previous !== $slug) {
                UserReferralSlug::firstOrCreate(['slug' => $previous], ['user_referral_id' => $referral->id]);
            }
            UserReferralSlug::where('user_referral_id', $referral->id)->where('slug', $slug)->forceDelete();
            $referral->slug = $slug;
            $referral->save();
        });
    }

    private function part(string $text, int $max): string
    {
        return trim(substr(Str::slug($text), 0, $max), '-');
    }
}
