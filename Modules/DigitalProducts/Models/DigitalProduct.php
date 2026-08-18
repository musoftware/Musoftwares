<?php

namespace Modules\DigitalProducts\Models;

use App\Models\Currency;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DigitalProduct extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'digital_products';

    protected $fillable = [
        'title',
        'slug',
        'description',
        'short_description',
        'category_id',
        'price',
        'currency_id',
        'is_free',
        'file_path',
        'cover_image_path',
        'sample_file_path',
        'file_size',
        'page_count',
        'author_name',
        'publisher',
        'publication_year',
        'language',
        'download_count',
        'view_count',
        'is_published',
        'is_featured',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_free' => 'boolean',
        'is_published' => 'boolean',
        'is_featured' => 'boolean',
        'page_count' => 'integer',
        'file_size' => 'integer',
        'download_count' => 'integer',
        'view_count' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->slug)) {
                $baseSlug = Str::slug($product->title);
                if (empty($baseSlug)) {
                    $baseSlug = 'book-' . Str::random(6);
                }
                $slug = $baseSlug;
                $counter = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $baseSlug . '-' . $counter++;
                }
                $product->slug = $slug;
            }

            if ($product->price <= 0) {
                $product->is_free = true;
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(DigitalCategory::class, 'category_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(DigitalProductDownload::class, 'digital_product_id');
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(DigitalProductPurchase::class, 'digital_product_id');
    }

    public function getFormattedPriceAttribute(): string
    {
        if ($this->is_free || $this->price <= 0) {
            return app()->getLocale() === 'ar' ? 'مجاناً' : 'Free';
        }

        $currencyCode = $this->currency?->code ?? 'USD';
        return number_format((float)$this->price, 2) . ' ' . $currencyCode;
    }

    public function getFormattedFileSizeAttribute(): string
    {
        if (!$this->file_size) {
            return '—';
        }

        $bytes = (float) $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getCoverUrlAttribute(): string
    {
        if ($this->cover_image_path) {
            if (Str::startsWith($this->cover_image_path, ['http://', 'https://'])) {
                return $this->cover_image_path;
            }
            return asset($this->cover_image_path);
        }

        return asset('images/default-book-cover.svg');
    }

    public function isPurchasedBy(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $this->purchases()->where('user_id', $user->id)->exists();
    }
}
