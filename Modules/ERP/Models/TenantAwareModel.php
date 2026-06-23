<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Modules\ERP\Models\Scopes\TenantScope;
use Modules\ERP\Infrastructure\Context\TenantContext;

abstract class TenantAwareModel extends Model
{
    use SoftDeletes;

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            if (empty($model->tenant_id)) {
                $tenantId = app(TenantContext::class)->getTenantId();

                if (!$tenantId && session()->has('tenant_id')) {
                    $tenantId = session('tenant_id');
                }

                if (!$tenantId && auth()->check()) {
                    $user = auth()->user();
                    if (isset($user->tenant_id)) {
                        $tenantId = $user->tenant_id;
                    } else {
                        $tenantId = $user->tenant?->id;
                    }
                }

                if ($tenantId) {
                    $model->tenant_id = $tenantId;
                }
            }
        });
    }
}
