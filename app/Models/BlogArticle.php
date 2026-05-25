<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

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
        'published_at'
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
    }

    public static function generateUniqueSlug($title)
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $count = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count++;
        }

        return $slug;
    }

    public function service()
    {
        return $this->belongsTo(\Modules\Marketplace\Models\Service::class);
    }

    public function translations()
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
            ->where('published_at', '<=', now());
    }
}
