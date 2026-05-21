<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'title',
        'bio',
        'image',
        'experience_years',
        'projects_count',
        'skills',
        'social_links',
        'email',
        'location',
        'achievements',
        'order',
        'is_active',
    ];

    protected $casts = [
        'skills' => 'array',
        'social_links' => 'array',
        'achievements' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Scope to get only active team members
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by display order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    /**
     * Get Facebook profile URL
     */
    public function getFacebookUrlAttribute()
    {
        return $this->social_links['facebook'] ?? null;
    }

    /**
     * Get LinkedIn profile URL
     */
    public function getLinkedinUrlAttribute()
    {
        return $this->social_links['linkedin'] ?? null;
    }

    /**
     * Get Twitter profile URL
     */
    public function getTwitterUrlAttribute()
    {
        return $this->social_links['twitter'] ?? null;
    }

    /**
     * Get Instagram profile URL
     */
    public function getInstagramUrlAttribute()
    {
        return $this->social_links['instagram'] ?? null;
    }

    /**
     * Get GitHub profile URL
     */
    public function getGithubUrlAttribute()
    {
        return $this->social_links['github'] ?? null;
    }
}
