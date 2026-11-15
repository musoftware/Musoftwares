<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/** A name an invite link had before the client changed it; links shared with it still reach the client. */
class UserReferralSlug extends Model
{
    use SoftDeletes;

    protected $fillable = ['user_referral_id', 'slug'];

    public function referral(): BelongsTo
    {
        return $this->belongsTo(UserReferral::class, 'user_referral_id');
    }
}
