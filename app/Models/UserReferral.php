<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserReferral extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'title', 'key', 'slug', 'views', 'registered'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Resolve a ref string (key, custom slug, or referrer's username/slug) to the UserReferral.
     */
    public static function resolveRef(string $ref): ?self
    {
        $ref = trim($ref);
        if ($ref === '') {
            return null;
        }
        // 1) By referral key (existing hash)
        $referral = self::where('key', $ref)->first();
        if ($referral) {
            return $referral;
        }
        // 2) By referral slug (custom link slug)
        $referral = self::where('slug', $ref)->first();
        if ($referral) {
            return $referral;
        }
        // 3) By user slug (username-style: same as users.slug)
        $user = User::where('slug', $ref)->first();
        if ($user) {
            return $user->referrals()->first();
        }
        return null;
    }

    public static function IncViewRef($referral)
    {
        $ref = self::resolveRef($referral);
        if ($ref !== null) {
            $ref->increment('views');
        }
    }

    public static function TotalViews()
    {
        return UserReferral::query()->sum('views');
    }

    public static function TotalRegisters()
    {
        return UserReferral::query()->sum('registered');
    }
}
