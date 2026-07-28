<?php

namespace Modules\WhatsappSender\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TelegramBot extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'whatsapp_business_id',
        'name',
        'username',
        'token',
        'status',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(WhatsappBusiness::class, 'whatsapp_business_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(WhatsappLog::class, 'telegram_bot_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(WhatsappSchedule::class, 'telegram_bot_id');
    }

    public function subscribers(): HasMany
    {
        return $this->hasMany(TelegramSubscriber::class, 'telegram_bot_id');
    }

    public function subscriberGroups(): HasMany
    {
        return $this->hasMany(TelegramSubscriberGroup::class, 'telegram_bot_id');
    }
}
