<?php

namespace Modules\DigitalProducts\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DigitalProductDownload extends Model
{
    use HasFactory;

    protected $table = 'digital_product_downloads';

    protected $fillable = [
        'digital_product_id',
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
