<?php

namespace Modules\WhatsappSender\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'whatsapp_account_id',
        'telegram_bot_id',
        'whatsapp_business_id',
        'recipient_phone',
        'channel',
        'cost_charged',
        'message_type',
        'message_body',
        'status',
        'direction',
        'meta_message_id',
        'error_message',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
        'cost_charged' => 'decimal:4',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(WhatsappAccount::class, 'whatsapp_account_id');
    }

    public function telegramBot(): BelongsTo
    {
        return $this->belongsTo(TelegramBot::class, 'telegram_bot_id');
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(WhatsappBusiness::class, 'whatsapp_business_id');
    }
}
