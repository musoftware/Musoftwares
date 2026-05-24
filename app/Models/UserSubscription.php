<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSubscription extends Model
{
    protected $table = 'user_subscriptions';

    protected $fillable = [
        'client_id',
        'plan_id',
        'status',
        'started_at',
        'expires_at',
        'auto_renew',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'auto_renew' => 'boolean',
    ];

    /**
     * The user (client) who owns this subscription.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /**
     * The plan this subscription is linked to.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(ModulePlan::class, 'plan_id');
    }
}
