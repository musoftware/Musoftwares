<?php

namespace Modules\ERP\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Modules\ERP\Infrastructure\Context\TenantContext;

class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param Builder $builder
     * @param Model $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        $tenantId = app(TenantContext::class)->getTenantId();

        // Fallback to session for web routes where context is not explicitly set yet
        if (!$tenantId && session()->has('tenant_id')) {
            $tenantId = session('tenant_id');
        }

        // Fallback for API or direct use without session
        if (!$tenantId && auth()->check()) {
            $user = auth()->user();
            if (isset($user->tenant_id)) {
                $tenantId = $user->tenant_id;
            } else {
                $tenantId = $user->tenant?->id;
            }
        }

        if ($tenantId) {
            $builder->where($model->getTable() . '.tenant_id', '=', $tenantId);
        } else {
            // Unresolvable tenant context
            if (app()->runningInConsole()) {
                // To avoid leaking data in console/jobs, force zero results
                $builder->whereRaw('0 = 1');
            } else {
                // For web/api, throw an exception
                throw new \App\Exceptions\TenantCouldNotBeIdentifiedException('Tenant could not be identified for the current context.');
            }
        }
    }
}
