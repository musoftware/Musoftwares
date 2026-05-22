<?php

namespace App\Models\Communication;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Community extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'cover_image',
        'creator_id',
        'type',
        'status',
        'member_count',
        'post_count',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($community) {
            if (empty($community->slug)) {
                $community->slug = Str::slug($community->name);
            }
        });
    }

    /**
     * Get the creator of the community.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Get the members of the community.
     */
    public function members()
    {
        return $this->belongsToMany(User::class, 'community_members')
            ->withPivot(['role', 'status', 'joined_at'])
            ->withTimestamps();
    }

    /**
     * Get the active members of the community.
     */
    public function activeMembers()
    {
        return $this->members()->wherePivot('status', 'active');
    }

    /**
     * Get the admins of the community.
     */
    public function admins()
    {
        return $this->members()->wherePivot('role', 'admin');
    }

    /**
     * Get the moderators of the community.
     */
    public function moderators()
    {
        return $this->members()->wherePivot('role', 'moderator');
    }

    /**
     * Get the posts in this community.
     */
    public function posts()
    {
        return $this->hasMany(SocialPost::class, 'community_id');
    }

    /**
     * Check if a user is a member of this community.
     */
    public function hasMember(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->exists();
    }

    /**
     * Check if a user is an admin of this community.
     */
    public function isAdmin(User $user): bool
    {
        return $this->members()
            ->where('user_id', $user->id)
            ->wherePivot('role', 'admin')
            ->exists();
    }

    /**
     * Check if a user is a moderator of this community.
     */
    public function isModerator(User $user): bool
    {
        return $this->members()
            ->where('user_id', $user->id)
            ->wherePivot('role', 'moderator')
            ->exists();
    }

    /**
     * Check if a user can moderate this community.
     */
    public function canModerate(User $user): bool
    {
        return $this->isAdmin($user) || $this->isModerator($user);
    }

    /**
     * Add a member to the community.
     */
    public function addMember(User $user, string $role = 'member'): void
    {
        $this->members()->attach($user->id, [
            'role' => $role,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $this->increment('member_count');
    }

    /**
     * Remove a member from the community.
     */
    public function removeMember(User $user): void
    {
        $this->members()->detach($user->id);
        $this->decrement('member_count');
    }

    /**
     * Scope for active communities.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for public communities.
     */
    public function scopePublic($query)
    {
        return $query->where('type', 'public');
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }
}