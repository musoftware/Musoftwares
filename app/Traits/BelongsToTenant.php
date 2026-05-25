<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant()
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (auth()->check()) {
                // If the user is an admin, they might have their own workspace (user_id = admin's id)
                // So they only see their own records by default.
                // Wait, if an admin wants to see ALL records across the platform, they can use withoutGlobalScope('tenant').
                // Since Admin uses this module as a client, it's correct to scope it to their user ID.
                $builder->where('user_id', auth()->id());
            }
        });

        static::creating(function ($model) {
            if (auth()->check() && empty($model->user_id)) {
                $model->user_id = auth()->id();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
