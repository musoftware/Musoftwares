<?php

namespace Modules\Tools\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;

class WaAccount extends Model
{
    use SoftDeletes;

    protected $table = 'wa_accounts';

    protected $fillable = [
        'user_id', 'label', 'phone_number', 'session_id', 'status',
        'health_score', 'trust_grade', 'warmup_day', 'daily_limit',
        'proxy', 'pool_numbers', 'capabilities',
        'warmup_started_at', 'last_seen_at', 'banned_at',
    ];

    protected $casts = [
        'pool_numbers'      => 'array',
        'capabilities'      => 'array',
        'warmup_started_at' => 'datetime',
        'last_seen_at'      => 'datetime',
        'banned_at'         => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(WaCampaign::class, 'account_ids', 'session_id');
    }

    public function qualityEvents(): HasMany
    {
        return $this->hasMany(WaQualityEvent::class, 'account_id', 'session_id');
    }

    public function isWarmedUp(): bool
    {
        return $this->warmup_day >= 14 && $this->health_score >= 70;
    }

    public function isBanned(): bool
    {
        return $this->status === 'banned';
    }
}
