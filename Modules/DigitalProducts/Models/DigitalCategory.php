<?php

namespace Modules\DigitalProducts\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class DigitalCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'digital_categories';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    public function products(): HasMany
    {
        return $this->hasMany(DigitalProduct::class, 'category_id');
    }

    public function publishedProducts(): HasMany
    {
        return $this->hasMany(DigitalProduct::class, 'category_id')
            ->where('is_published', true);
    }
}
