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

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string|null $description
 * @property string|null $short_description
 * @property int|null $category_id
 * @property float|string $price
 * @property int|null $currency_id
 * @property bool $is_free
 * @property bool $has_free_edition
 * @property string|null $free_edition_title
 * @property string|null $free_edition_file_path
 * @property string|null $free_edition_cover_path
 * @property int|null $free_edition_page_count
 * @property int|null $free_edition_file_size
 * @property int $free_edition_download_count
 * @property string $file_path
 * @property string|null $cover_image_path
 * @property string|null $sample_file_path
 * @property int|null $file_size
 * @property int|null $page_count
 * @property string|null $author_name
 * @property string|null $publisher
 * @property string|null $publication_year
 * @property string|null $language
 * @property int $download_count
 * @property int $view_count
 * @property bool $is_published
 * @property bool $is_featured
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string|null $meta_keywords
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read string $cover_url
 * @property-read string $formatted_price
 * @property-read string $formatted_file_size
 * @property-read string $free_edition_cover_url
 * @property-read string $formatted_free_edition_file_size
 * @property-read \Modules\DigitalProducts\Models\DigitalCategory|null $category
 * @property-read \App\Models\Currency|null $currency
 */
class DigitalProduct extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'digital_products';

    protected $appends = [
        'cover_url',
        'formatted_price',
        'formatted_file_size',
        'free_edition_cover_url',
        'formatted_free_edition_file_size',
    ];

    protected $fillable = [
        'title',
        'slug',
        'description',
        'short_description',
        'category_id',
        'price',
        'currency_id',
        'is_free',
        'has_free_edition',
        'free_edition_title',
        'free_edition_file_path',
        'free_edition_cover_path',
        'free_edition_page_count',
        'free_edition_file_size',
        'free_edition_download_count',
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
        'has_free_edition' => 'boolean',
        'free_edition_page_count' => 'integer',
        'free_edition_file_size' => 'integer',
        'free_edition_download_count' => 'integer',
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

    public function getFormattedFreeEditionFileSizeAttribute(): string
    {
        if (!$this->free_edition_file_size) {
            return '—';
        }

        $bytes = (float) $this->free_edition_file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getFreeEditionCoverUrlAttribute(): string
    {
        if ($this->free_edition_cover_path) {
            if (Str::startsWith($this->free_edition_cover_path, ['http://', 'https://'])) {
                return $this->free_edition_cover_path;
            }
            return asset($this->free_edition_cover_path);
        }

        return $this->cover_url;
    }

    public function hasDualEditions(): bool
    {
        return $this->has_free_edition && !empty($this->free_edition_file_path);
    }

    public function isPurchasedBy(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $this->purchases()->where('user_id', $user->id)->exists();
    }
}
