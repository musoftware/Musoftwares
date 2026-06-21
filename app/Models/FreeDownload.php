<?php

namespace App\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class FreeDownload extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'title',
        'description',
        'programming_language',
        'image',
        'file_path',
        'original_filename',
        'is_active',
        'order_column',
    ];

    /**
     * Available programming languages for the dropdown.
     */
    public static function programmingLanguageOptions(): array
    {
        return [
            '' => __('general.select_language'),
            'PHP' => 'PHP',
            'JavaScript' => 'JavaScript',
            'TypeScript' => 'TypeScript',
            'Python' => 'Python',
            'Java' => 'Java',
            'C#' => 'C#',
            'C++' => 'C++',
            'Ruby' => 'Ruby',
            'Go' => 'Go',
            'Rust' => 'Rust',
            'Swift' => 'Swift',
            'Kotlin' => 'Kotlin',
            'Dart' => 'Dart',
            'VBA' => 'VBA',
            'SQL' => 'SQL',
            'HTML/CSS' => 'HTML/CSS',
            'Other' => __('general.other'),
        ];
    }

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Scope for active items only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Order by order_column then by id.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order_column')->orderBy('id');
    }

    /**
     * Get public URL for image (storage).
     */
    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) {
            return null;
        }
        return Storage::url($this->image);
    }

    /**
     * Check if this item has a downloadable file.
     */
    public function hasFile(): bool
    {
        return !empty($this->file_path) && Storage::disk('public')->exists($this->file_path);
    }
}
