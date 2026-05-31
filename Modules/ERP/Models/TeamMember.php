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

    public const ROLE_ADMIN = 'admin';
    public const ROLE_SALES_AGENT = 'sales_agent';
    public const ROLE_SALES_MANAGER = 'sales_manager';
    public const ROLE_SUPPORT_AGENT = 'support_agent';
    public const ROLE_SUPPORT_MANAGER = 'support_manager';
    public const ROLE_MARKETING = 'marketing';
    public const ROLE_CALL_CENTER = 'call_center';
    public const ROLE_ACCOUNT_MANAGER = 'account_manager';
    public const ROLE_BRANCH_MANAGER = 'branch_manager';
    public const ROLE_SOCIAL_MEDIA = 'social_media';

    // Legacy roles
    public const ROLE_LEGACY_MANAGER = 'manager';
    public const ROLE_LEGACY_MEMBER = 'member';

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

    public static function getBasicRoles(): array
    {
        return [
            self::ROLE_ADMIN => __('erp.roles_admin'),
            self::ROLE_SALES_AGENT => __('erp.roles_sales_agent'),
        ];
    }

    public static function getAdvancedRoles(): array
    {
        return [
            self::ROLE_SALES_MANAGER => __('erp.roles_sales_manager'),
            self::ROLE_SUPPORT_AGENT => __('erp.roles_support_agent'),
            self::ROLE_SUPPORT_MANAGER => __('erp.roles_support_manager'),
            self::ROLE_MARKETING => __('erp.roles_marketing'),
            self::ROLE_CALL_CENTER => __('erp.roles_call_center'),
            self::ROLE_ACCOUNT_MANAGER => __('erp.roles_account_manager'),
            self::ROLE_BRANCH_MANAGER => __('erp.roles_branch_manager'),
            self::ROLE_SOCIAL_MEDIA => __('erp.roles_social_media'),
        ];
    }

    public static function getAllRoles(): array
    {
        return array_merge(self::getBasicRoles(), self::getAdvancedRoles(), [
            self::ROLE_LEGACY_MANAGER => __('erp.roles_legacy_manager'),
            self::ROLE_LEGACY_MEMBER => __('erp.roles_legacy_member'),
        ]);
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
        return in_array($this->role, [
            self::ROLE_LEGACY_MANAGER,
            self::ROLE_ADMIN,
            self::ROLE_SALES_MANAGER,
            self::ROLE_SUPPORT_MANAGER,
            self::ROLE_BRANCH_MANAGER,
        ]);
    }

    /**
     * Helper to check if user has member role.
     */
    public function isMember(): bool
    {
        return in_array($this->role, [
            self::ROLE_LEGACY_MEMBER,
            self::ROLE_SALES_AGENT,
            self::ROLE_SUPPORT_AGENT,
            self::ROLE_CALL_CENTER,
            self::ROLE_ACCOUNT_MANAGER,
            self::ROLE_MARKETING,
            self::ROLE_SOCIAL_MEDIA,
        ]);
    }

    /**
     * Helper to check if team member is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if the tenant owner has an active subscription to a specific module.
     * This allows Auth::user()->hasModuleSubscription() to work transparently for TeamMembers.
     */
    public function hasModuleSubscription(string $module): bool
    {
        return $this->tenant && $this->tenant->user ? $this->tenant->user->hasModuleSubscription($module) : false;
    }

    /**
     * Check if the tenant owner has any active subscription.
     */
    public function hasSubscription(): bool
    {
        return $this->tenant && $this->tenant->user ? $this->tenant->user->hasSubscription() : false;
    }
}
