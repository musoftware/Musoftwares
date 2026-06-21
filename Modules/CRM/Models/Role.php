<?php

namespace Modules\CRM\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use SoftDeletes;

    protected $table = 'crm_roles';

    protected $fillable = [
        'workspace_id',
        'name',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'crm_role_permissions');
    }

    public function hasPermission(string $permission): bool
    {
        return $this->permissions()->where('name', $permission)->exists();
    }
}
