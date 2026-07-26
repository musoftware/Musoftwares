<?php

namespace Modules\WhatsappSender\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WhatsappBusiness extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'wallet_balance',
        'currency',
        'per_message_fee',
        'status',
        'webhook_verify_token',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($business) {
            if (empty($business->webhook_verify_token)) {
                $business->webhook_verify_token = 'biz_wt_' . \Illuminate\Support\Str::random(24);
            }
        });
    }

    protected $casts = [
        'wallet_balance' => 'decimal:4',
        'per_message_fee' => 'decimal:4',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(WhatsappAccount::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WhatsappTransaction::class)->orderBy('created_at', 'desc');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(WhatsappLog::class)->orderBy('created_at', 'desc');
    }
}
