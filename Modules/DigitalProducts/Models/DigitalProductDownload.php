<?php

namespace Modules\DigitalProducts\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $digital_product_id
 * @property string $edition_type
 * @property int|null $user_id
 * @property string|null $email
 * @property string $download_token
 * @property \Carbon\Carbon $token_expires_at
 * @property int $download_count
 * @property \Carbon\Carbon|null $last_downloaded_at
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read \Modules\DigitalProducts\Models\DigitalProduct|null $product
 * @property-read \App\Models\User|null $user
 */
class DigitalProductDownload extends Model
{
    use HasFactory;

    protected $table = 'digital_product_downloads';

    protected $fillable = [
        'digital_product_id',
        'edition_type',
        'user_id',
        'email',
        'download_token',
        'token_expires_at',
        'download_count',
        'last_downloaded_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'last_downloaded_at' => 'datetime',
        'download_count' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($download) {
            if (empty($download->download_token)) {
                $download->download_token = Str::random(64);
            }
            if (empty($download->token_expires_at)) {
                $hours = config('digitalproducts.download_token_lifetime_hours', 48);
                $download->token_expires_at = now()->addHours($hours);
            }
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(DigitalProduct::class, 'digital_product_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isValid(): bool
    {
        return $this->token_expires_at->isFuture();
    }
}
