<?php

namespace Modules\WhatsappSender\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotFlow extends Model
{
    protected $fillable = [
        'whatsapp_business_id',
        'channel',
        'telegram_bot_id',
        'name',
        'is_active',
        'trigger_type',
        'trigger_keywords',
        'nodes',
        'edges',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'trigger_keywords' => 'array',
        'nodes' => 'array',
        'edges' => 'array',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(WhatsappBusiness::class, 'whatsapp_business_id');
    }

    public function telegramBot(): BelongsTo
    {
        return $this->belongsTo(TelegramBot::class, 'telegram_bot_id');
    }
}
