<?php

namespace Modules\SoftwareStore\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A client who sells our programs under their own brand. Prices and the share are worked out
 * in WhiteLabelService; this model only holds the brand and the price list.
 *
 * @property int $id
 * @property int $user_id
 * @property string $slug
 * @property string $brand_name
 * @property string|null $logo_path
 * @property string $accent_color
 * @property string|null $support_whatsapp
 * @property string|null $support_email
 * @property string|null $website_url
 * @property array|null $prices
 * @property Carbon|null $subscription_expires_at
 */
class WhiteLabelPartner extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'prices' => 'array',
        'subscription_expires_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(SoftwareOrder::class);
    }

    public function isActive(): bool
    {
        return $this->subscription_expires_at !== null && $this->subscription_expires_at->isFuture();
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? asset('uploads/'.$this->logo_path) : null;
    }

    public function whatsappNumber(): ?string
    {
        return preg_replace('/\D+/', '', (string) $this->support_whatsapp) ?: null;
    }

    public function storeUrl(): string
    {
        return route('white-label.store', $this);
    }

    /** What the desktop programs show instead of our name, logo and WhatsApp. */
    public function brand(): array
    {
        return [
            'name' => $this->brand_name,
            'logo_url' => $this->logoUrl(),
            'accent_color' => $this->accent_color,
            'whatsapp_number' => $this->whatsappNumber(),
            'email' => $this->support_email,
            'website_url' => $this->website_url,
            'store_url' => $this->storeUrl(),
        ];
    }
}
