<?php

namespace Modules\WhatsappSender\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramSubscriber extends Model
{
    protected $fillable = [
        'telegram_bot_id',
        'telegram_subscriber_group_id',
        'chat_id',
        'username',
        'first_name',
        'last_name',
        'custom_fields',
    ];

    protected $casts = [
        'custom_fields' => 'array',
    ];

    public function bot(): BelongsTo
    {
        return $this->belongsTo(TelegramBot::class, 'telegram_bot_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(TelegramSubscriberGroup::class, 'telegram_subscriber_group_id');
    }
}
