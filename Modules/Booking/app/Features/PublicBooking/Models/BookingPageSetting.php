<?php

namespace Modules\Booking\app\Features\PublicBooking\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class BookingPageSetting extends Model
{
    use SoftDeletes;

    protected $table = 'booking_page_settings';

    protected $fillable = [
        'tenant_id',
        'slug',
        'title',
        'description',
        'primary_color',
        'logo_path',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        // Scope queries to current tenant when active
        static::addGlobalScope('tenant', function (Builder $builder) {
            $tenantId = app()->bound('currentTenant') ? app('currentTenant')->id : null;
            if (!$tenantId && auth()->check()) {
                $tenantId = (app()->bound('currentTenant') ? app('currentTenant')->id : auth()->id());
            }
            
            if ($tenantId) {
                $builder->where('tenant_id', $tenantId);
            }
        });
        
        static::creating(function ($model) {
            if (!$model->tenant_id && auth()->check()) {
                $model->tenant_id = (app()->bound('currentTenant') ? app('currentTenant')->id : auth()->id());
            }
            
            // Auto generate a slug if missing
            if (empty($model->slug)) {
                $model->slug = \Illuminate\Support\Str::slug($model->title ?? 'booking-page-' . uniqid());
            }
        });
    }
}
