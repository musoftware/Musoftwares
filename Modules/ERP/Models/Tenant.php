<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;

class Tenant extends Model
{
    protected $fillable = ['user_id', 'name', 'status', 'trial_ends_at', 'subscription_ends_at'];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function clients(): HasMany
    {
        return $this->hasMany(TenantClient::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function wallets(): HasMany
    {
        return $this->hasMany(ClientWallet::class);
    }
}
