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
        'client_name',
        'client_email',
        'client_mobile',
        'client_whatsapp',
        'wallet_balance',
        'currency',
        'per_message_fee',
        'bot_reply_fee',
        'status',
        'webhook_verify_token',
        'uuid',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($business) {
            if (empty($business->webhook_verify_token)) {
                $business->webhook_verify_token = 'biz_wt_' . \Illuminate\Support\Str::random(24);
            }
            if (empty($business->uuid)) {
                $business->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    protected $casts = [
        'wallet_balance' => 'decimal:4',
        'per_message_fee' => 'decimal:4',
        'bot_reply_fee' => 'decimal:4',
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

    public function templates(): HasMany
    {
        return $this->hasMany(WhatsappTemplate::class);
    }

    public function contactGroups(): HasMany
    {
        return $this->hasMany(WhatsappContactGroup::class);
    }

    public function telegramBots(): HasMany
    {
        return $this->hasMany(TelegramBot::class);
    }

    public function flows(): HasMany
    {
        return $this->hasMany(BotFlow::class);
    }
}
