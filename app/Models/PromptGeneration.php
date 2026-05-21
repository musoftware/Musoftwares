<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromptGeneration extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'template_id',
        'generation_type',
        'category',
        'user_input',
        'generated_prompt',
        'parameters',
        'image_path',
        'analysis_notes',
        'tags',
        'language',
        'is_favorite',
        'is_public',
        'share_title',
        'share_description',
        'likes_count',
    ];

    protected $casts = [
        'parameters' => 'array',
        'tags' => 'array',
        'is_favorite' => 'boolean',
        'is_public' => 'boolean',
        'likes_count' => 'integer',
        'user_id' => 'integer',
        'template_id' => 'integer',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function template()
    {
        return $this->belongsTo(PromptTemplate::class, 'template_id');
    }

    public function iterations()
    {
        return $this->hasMany(PromptGenerationIteration::class, 'parent_generation_id');
    }

    public function favorites()
    {
        return $this->hasMany(PromptFavorite::class, 'generation_id');
    }

    // Scopes
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeFavorites($query)
    {
        return $query->where('is_favorite', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('generation_type', $type);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Methods
    public function toggleFavorite()
    {
        $this->is_favorite = !$this->is_favorite;
        $this->save();
    }
}
