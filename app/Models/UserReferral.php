<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserReferral extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = ['user_id', 'title', 'key', 'slug', 'views', 'registered'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Resolve a ref string (key, custom slug, or referrer's username/slug) to the UserReferral.
     * Uses one query against user_referrals (key OR slug) plus one against users (slug).
     */
    public static function resolveRef(string $ref): ?self
    {
        $ref = trim($ref);
        if ($ref === '') {
            return null;
        }

        $referral = self::query()
            ->where(function (Builder $q) use ($ref) {
                $q->where('key', $ref)->orWhere('slug', $ref);
            })
            ->first();
        if ($referral) {
            return $referral;
        }

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