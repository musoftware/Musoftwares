<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = ['name'];
    public function permissions() {
        return $this->belongsToMany(Permission::class,'roles_permissions');
    }

    public function users() {
        return $this->belongsToMany(User::class,'users_roles');
    }

    public static function createRule($name, $slug)
    {
        if (Role::where('slug', $slug)->count() == 0) {
            $permission = new Role();
            $permission->name = $name;
            $permission->slug = $slug;
            $permission->save();
            return $permission;
        } else {
            return Role::where('slug', $slug)->first();
        }
    }

}
