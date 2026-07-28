<?php

namespace Modules\WhatsappSender\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TelegramSubscriberGroup extends Model
{
    protected $fillable = [
        'telegram_bot_id',
        'name',
        'description',
    ];

    public function bot(): BelongsTo
    {
        return $this->belongsTo(TelegramBot::class, 'telegram_bot_id');
    }

    public function subscribers(): HasMany
    {
        return $this->hasMany(TelegramSubscriber::class, 'telegram_subscriber_group_id');
    }
}
