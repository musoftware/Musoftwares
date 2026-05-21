<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class PremiumTool extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'category',
        'access_level',
        'is_active',
        'sort_order',
        'features',
        'api_endpoints'
    ];

    protected $casts = [
        'features' => 'array',
        'api_endpoints' => 'array',
        'is_active' => 'boolean'
    ];

    public function memberships()
    {
        return $this->belongsToMany(Membership::class, 'membership_premium_tools')
                    ->withPivot('is_enabled')
                    ->withTimestamps();
    }

    public function usage()
    {
        return $this->hasMany(PremiumToolUsage::class);
    }

    public function userUsage()
    {
        return $this->hasMany(PremiumToolUsage::class)->where('user_id', Auth::id());
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByAccessLevel($query, $level)
    {
        return $query->where('access_level', $level);
    }

    public function isAccessibleByUser($user = null)
    {
        if (!$user) {
            $user = Auth::user();
        }

        if (!$user) {
            return false;
        }

        // Check if user has active membership with access to this tool
        $activeMembership = $user->memberships()
            ->where('expires_at', '>', now())
            ->whereHas('membership.premiumTools', function ($query) {
                $query->where('premium_tool_id', $this->id);
            })
            ->first();

        return $activeMembership !== null;
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }
}
