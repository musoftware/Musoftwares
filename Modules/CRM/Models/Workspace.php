<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class Workspace extends Model
{
    use SoftDeletes, HasFactory;

    protected $table = 'crm_workspaces';

    protected $fillable = [
        'user_id',
        'name',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    /**
     * The User who owns this workspace (Billing Owner)
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Users who have access to this workspace
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'crm_workspace_users')
                    ->withPivot('role_id', 'is_active')
                    ->withTimestamps();
    }

    public function roles()
    {
        return $this->hasMany(Role::class);
    }

    /**
     * CRM Team Members assigned to this workspace.
     */
    public function teamMembers()
    {
        return $this->hasMany(CrmTeamMember::class);
    }

    protected static function newFactory()
    {
        return \Modules\CRM\Database\Factories\WorkspaceFactory::new();
    }
}
