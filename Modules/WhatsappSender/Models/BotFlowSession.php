<?php

namespace Modules\WhatsappSender\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotFlowSession extends Model
{
    protected $fillable = [
        'channel',
        'whatsapp_business_id',
        'telegram_bot_id',
        'subscriber_identifier',
        'bot_flow_id',
        'current_node_id',
        'context_data',
        'expires_at',
    ];

    protected $casts = [
        'context_data' => 'array',
        'expires_at' => 'datetime',
    ];

    public function flow(): BelongsTo
    {
        return $this->belongsTo(BotFlow::class, 'bot_flow_id');
    }
}
