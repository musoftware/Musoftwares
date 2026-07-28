<?php

namespace Modules\WhatsappSender\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'whatsapp_business_id',
        'whatsapp_account_id',
        'telegram_bot_id',
        'whatsapp_contact_group_id',
        'recipient_phone',
        'channel',
        'message_type',
        'message_body',
        'template_name',
        'template_language',
        'template_components',
        'scheduled_at',
        'status',
        'error_message',
    ];

    protected $casts = [
        'template_components' => 'array',
        'scheduled_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(WhatsappBusiness::class, 'whatsapp_business_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(WhatsappAccount::class, 'whatsapp_account_id');
    }

    public function telegramBot(): BelongsTo
    {
        return $this->belongsTo(TelegramBot::class, 'telegram_bot_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(WhatsappContactGroup::class, 'whatsapp_contact_group_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending')->where('scheduled_at', '<=', now());
    }
}
