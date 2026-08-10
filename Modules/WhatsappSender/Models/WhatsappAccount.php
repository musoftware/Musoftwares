<?php

namespace Modules\WhatsappSender\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsappAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'whatsapp_business_id',
        'name',
        'phone_number_id',
        'waba_id',
        'access_token',
        'status',
        'facebook_user_id',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    protected $appends = [
        'display_phone_number',
    ];

    public function getDisplayPhoneNumberAttribute(): ?string
    {
        if (!empty($this->metadata['display_phone_number'])) {
            return $this->metadata['display_phone_number'];
        }

        if (!empty($this->metadata['phone_number'])) {
            return $this->metadata['phone_number'];
        }

        if ($this->relationLoaded('business') && $this->business) {
            if (!empty($this->business->client_whatsapp)) {
                return $this->business->client_whatsapp;
            }
            if (!empty($this->business->client_mobile)) {
                return $this->business->client_mobile;
            }
        }

        return null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(WhatsappBusiness::class, 'whatsapp_business_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(WhatsappLog::class);
    }
}
