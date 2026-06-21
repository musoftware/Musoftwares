<?php

namespace Modules\CRM\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class CrmTeamMember extends Authenticatable
{
    use SoftDeletes, Notifiable;

    protected $table = 'crm_team_members';

    // ── CRM Roles ──
    public const ROLE_MEMBER = 'member';
    public const ROLE_SALES_AGENT = 'sales_agent';
    public const ROLE_SOCIAL_MEDIA = 'social_media';
    public const ROLE_SUPPORT_AGENT = 'support_agent';
    public const ROLE_SUPPORT_MANAGER = 'support_manager';
    public const ROLE_SALES_MANAGER = 'sales_manager';
    public const ROLE_MANAGER = 'manager';

    protected $fillable = [
        'workspace_id',
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

    // ── Role Definitions ──

    public static function getBasicRoles(): array
    {
        return [
            self::ROLE_MEMBER => __('crm.role_member'),
            self::ROLE_SALES_AGENT => __('crm.role_telesales'),
        ];
    }

    public static function getAdvancedRoles(): array
    {
        return [
            self::ROLE_SOCIAL_MEDIA => __('crm.role_social_media'),
            self::ROLE_SUPPORT_AGENT => __('crm.role_support'),
            self::ROLE_SUPPORT_MANAGER => __('crm.role_support_manager'),
            self::ROLE_SALES_MANAGER => __('crm.role_sales_manager'),
            self::ROLE_MANAGER => __('crm.role_manager'),
        ];
    }

    public static function getAllRoles(): array
    {
        return array_merge(self::getBasicRoles(), self::getAdvancedRoles());
    }

    // ── Relationships ──

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    // ── Helpers ──

    public function isManager(): bool
    {
        return in_array($this->role, [
            self::ROLE_MANAGER,
            self::ROLE_SALES_MANAGER,
            self::ROLE_SUPPORT_MANAGER,
        ]);
    }

    public function isMember(): bool
    {
        return in_array($this->role, [
            self::ROLE_MEMBER,
            self::ROLE_SALES_AGENT,
            self::ROLE_SUPPORT_AGENT,
            self::ROLE_SOCIAL_MEDIA,
        ]);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check module subscription through the workspace owner.
     */
    public function hasModuleSubscription(string $module): bool
    {
        $owner = $this->workspace?->owner;
        return $owner ? $owner->hasModuleSubscription($module) : false;
    }
}
