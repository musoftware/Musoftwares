<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Marketplace\Models\Service;

class BlogArticle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'service_id',
        'language',
        'group_id',
        'title',
        'slug',
        'content',
        'excerpt',
        'featured_image',
        'meta_title',
        'meta_description',
        'variation_group',
        'cycle_number',
        'is_published',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'is_published' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($article) {
            if (empty($article->slug)) {
                $article->slug = static::generateUniqueSlug($article->title);
            }
        });

        static::updating(function ($article) {
            if (empty($article->slug)) {
                $article->slug = static::generateUniqueSlug($article->title);
            }
        });

        static::saving(function ($article) {
            if ($article->is_published && empty($article->published_at)) {
                $article->published_at = now();
            }
        });

        static::saved(function ($article) {
            try {
                \Illuminate\Support\Facades\Artisan::queue('marketplace:generate-ai-files');
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Queueing generate-ai-files failed on blog saved: ' . $e->getMessage());
            }
        });

        static::deleted(function ($article) {
            try {
                \Illuminate\Support\Facades\Artisan::queue('marketplace:generate-ai-files');
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Queueing generate-ai-files failed on blog deleted: ' . $e->getMessage());
            }
        });
    }

    public static function generateUniqueSlug($title)
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $count = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = $originalSlug.'-'.$count++;
        }

        return $slug;
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(BlogArticle::class, 'group_id', 'group_id');
    }

    /**
     * Get the slug or fallback to English translation slug if empty
     */
    public function getSlugOrFallback()
    {
        // Check if slug is empty or just a dash followed by a number (e.g., "-1")
        if (empty($this->slug) || preg_match('/^-\d+$/', $this->slug)) {
            // Try to find English translation
            $enTranslation = $this->translations()->where('language', 'en')->first();

            return $enTranslation ? $enTranslation->slug : $this->slug;
        }

        return $this->slug;
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true)
            ->where(function ($q) {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }
}
