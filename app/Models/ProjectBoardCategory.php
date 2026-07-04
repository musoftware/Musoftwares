<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectBoardCategory extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'is_system' => 'boolean',
        'sort' => 'integer',
    ];

    /** Canonical defaults seeded for every project that doesn't already have them. */
    public const DEFAULTS = [
        [
            'slug' => 'urgent',
            'name' => 'Urgent',
            'name_ar' => 'عاجل',
            'color' => 'rose',
            'text_color' => 'rose',
            'sort' => 0,
        ],
        [
            'slug' => 'important',
            'name' => 'Important',
            'name_ar' => 'مهم',
            'color' => 'amber',
            'text_color' => 'amber',
            'sort' => 10,
        ],
        [
            'slug' => 'normal',
            'name' => 'Normal',
            'name_ar' => 'عادي',
            'color' => 'slate',
            'text_color' => 'slate',
            'sort' => 20,
        ],
        [
            'slug' => 'idea',
            'name' => 'Idea',
            'name_ar' => 'فكرة',
            'color' => 'sky',
            'text_color' => 'sky',
            'sort' => 30,
        ],
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function boardItems(): HasMany
    {
        return $this->hasMany(ProjectBoardItem::class, 'category_id');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort')->orderBy('id');
    }

    /**
     * Localized display label. Falls back to the English `name` when the active
     * locale has no Arabic translation.
     */
    public function localizedName(?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();

        if ($locale === 'ar' && ! empty($this->name_ar)) {
            return $this->name_ar;
        }

        return (string) ($this->name ?: $this->slug);
    }
}
