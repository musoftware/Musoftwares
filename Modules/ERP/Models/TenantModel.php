<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

abstract class TenantModel extends Model
{
    protected static function booted()
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (auth()->check() && session()->has('tenant_id')) {
                $builder->where('tenant_id', session('tenant_id'));
            }
        });

        static::creating(function ($model) {
            if (auth()->check() && session()->has('tenant_id') && empty($model->tenant_id)) {
                $model->tenant_id = session('tenant_id');
            }
        });
    }
}
