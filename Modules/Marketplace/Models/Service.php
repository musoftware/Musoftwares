<?php

namespace Modules\Marketplace\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string|null $title
 * @property string|null $tagline
 * @property string|null $description
 * @property array|null $gallery
 * @property string $cover_image
 * @property string $slug
 * @property string $url
 */
class Service extends Model
{
    use Searchable, SoftDeletes, HasFactory;

    protected $table = 'marketplace_services';

    protected $fillable = [
        'seller_id', 'category_id', 'title', 'slug', 'thumbnail', 'tagline', 'description', 'auto_reply', 'status',
        'title_translations', 'tagline_translations', 'description_translations', 'auto_reply_translations',
        'tags', 'faq', 'requirements', 'gallery', 'video_url',
        'approved_at', 'approved_by', 'rejected_at', 'rejection_reason',
        'suspended_at', 'suspended_by', 'is_featured',
        'service_link', 'generate_serials', 'allow_random_serial', 'validity_days',
        'referral_commission_from', 'referral_commission_percentage', 'is_free'
    ];

    protected $appends = ['cover_image', 'thumbnail', 'slug', 'url'];

    protected $casts = [
        'is_featured'  => 'boolean',
        'is_free'      => 'boolean',
        'generate_serials' => 'boolean',
        'allow_random_serial' => 'boolean',
        'tags'         => 'array',
        'faq'          => 'array',
        'requirements' => 'array',
        'gallery'      => 'array',
        'title_translations' => 'array',
        'tagline_translations' => 'array',
        'description_translations' => 'array',
        'auto_reply_translations' => 'array',
    ];

    public function seller(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id');
    }

    public function packages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ServicePackage::class, 'service_id');
    }

    public function landingPage(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ServiceLandingPage::class, 'service_id');
    }

    public function orders()
    {
        return $this->hasManyThrough(ServiceOrder::class, ServicePackage::class, 'service_id', 'package_id');
    }

    public function reviews()
    {
        return $this->hasMany(ServiceReview::class, 'service_id');
    }

    public function extras()
    {
        return $this->hasMany(ServiceExtra::class, 'service_id');
    }

    public function discounts()
    {
        return $this->hasMany(ServiceDiscount::class, 'service_id');
    }

    public function serials()
    {
        return $this->hasMany(ServiceSerial::class, 'service_id');
    }

    public function getCoverImageAttribute()
    {
        if (!empty($this->gallery) && is_array($this->gallery) && isset($this->gallery[0])) {
            $path = $this->gallery[0];
            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                return $path;
            }
            $cleanPath = ltrim($path, '/');
            if (str_starts_with($cleanPath, 'storage/')) {
                $cleanPath = substr($cleanPath, 8);
            }
            if (str_starts_with($cleanPath, 'uploads/')) {
                $cleanPath = substr($cleanPath, 8);
            }
            return Storage::disk('public_uploads')->url($cleanPath);
        }
        return 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?auto=format&fit=crop&w=800&q=80';
    }

    public function getThumbnailAttribute()
    {
        if (!empty($this->attributes['thumbnail'])) {
            $path = $this->attributes['thumbnail'];
            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                return $path;
            }
            $cleanPath = ltrim($path, '/');
            if (str_starts_with($cleanPath, 'storage/')) {
                $cleanPath = substr($cleanPath, 8);
            }
            if (str_starts_with($cleanPath, 'uploads/')) {
                $cleanPath = substr($cleanPath, 8);
            }
            return Storage::disk('public_uploads')->url($cleanPath);
        }

        return $this->cover_image;
    }

    public function getSlugAttribute(): string
    {
        if (!empty($this->attributes['slug'])) {
            return $this->attributes['slug'];
        }
        $titleStr = (string)($this->title ?? '');
        return \Illuminate\Support\Str::slug($titleStr !== '' ? $titleStr : ('service-' . $this->id));
    }

    public function getUrlAttribute(): string
    {
        return route('marketplace.services.show', ['id' => $this->id, 'slug' => $this->slug]);
    }
}
