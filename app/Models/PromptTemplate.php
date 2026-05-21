<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromptTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_en',
        'name_ar',
        'description_en',
        'description_ar',
        'category',
        'subcategory',
        'template_structure',
        'example_output',
        'tone',
        'length',
        'tags',
        'usage_count',
        'is_active',
    ];

    protected $casts = [
        'tags' => 'array',
        'template_structure' => 'array',
        'is_active' => 'boolean',
        'usage_count' => 'integer',
    ];

    // Relations
    public function generations()
    {
        return $this->hasMany(PromptGeneration::class, 'template_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeBySubcategory($query, $subcategory)
    {
        return $query->where('subcategory', $subcategory);
    }

    // Methods
    public function incrementUsage()
    {
        $this->increment('usage_count');
    }
}
