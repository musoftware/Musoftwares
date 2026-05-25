<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $table = 'crm_permissions';

    protected $fillable = [
        'name',
        'group',
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'crm_role_permissions');
    }
}
