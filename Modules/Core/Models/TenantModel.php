<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

abstract class TenantModel extends Model
{
    protected static function booted()
    {
        static::addGlobalScope('tenant_id', function (Builder $builder) {
            if (auth()->check() && session()->has('tenant_id')) {
                // If user is admin, we might bypass. Assume 'admin' role or similar.
                $user = auth()->user();
                // Depending on the exact logic for admin bypass, e.g., $user->hasRole('admin')
                // The task description says "bypass scope for admin"
                // Assuming method isAdmin() or check for role. Let's use standard permission check or is_admin flag if available.
                // Looking at User model, it uses Spatie Permission: HasRoles.
                if (!$user->hasRole('admin')) {
                    $builder->where('tenant_id', session('tenant_id'));
                }
            }
        });

        static::creating(function ($model) {
            if (auth()->check() && session()->has('tenant_id') && empty($model->tenant_id)) {
                $user = auth()->user();
                if (!$user->hasRole('admin')) {
                    $model->tenant_id = session('tenant_id');
                }
            }
        });
    }

    public function notes(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(\Modules\Core\Models\AdminNote::class, 'noteable');
    }
}
