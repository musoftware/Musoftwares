<?php

namespace Modules\Booking\app\Core\Traits;

use Modules\Booking\app\Core\Scopes\TenantScope;

trait TenantAwareModel
{
    /**
     * Boot the tenant aware trait for a model.
     *
     * @return void
     */
    protected static function bootTenantAwareModel()
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            if (!$model->tenant_id) {
                $model->tenant_id = currentTenant();
            }
        });
    }
}
