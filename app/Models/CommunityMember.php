<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommunityMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'community_id',
        'user_id',
        'role',
        'status',
        'joined_at',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
    ];

    /**
     * Get the community that owns the member.
     */
    public function community()
    {
        return $this->belongsTo(Community::class);
    }

    /**
     * Get the user that owns the member.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for active members.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for admins.
     */
    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    /**
     * Scope for moderators.
     */
    public function scopeModerators($query)
    {
        return $query->where('role', 'moderator');
    }
}
