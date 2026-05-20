<?php

namespace Modules\ERP\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class TeamMember extends Authenticatable
{
    use Notifiable;

    protected $table = 'erp_team_members';

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'role',
        'status',
        'invited_by',
        'invited_at',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'invited_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Get the tenant that owns the team member.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the user who invited the team member.
     */
    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Helper to check if user has manager role.
     */
    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    /**
     * Helper to check if user has member role.
     */
    public function isMember(): bool
    {
        return $this->role === 'member';
    }

    /**
     * Helper to check if team member is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
