<?php

namespace Modules\Booking\app\Features\OnlinePage\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class PublicPage extends Model
{
    use SoftDeletes;

    protected $table = 'booking_public_pages';

    protected $fillable = [
        'tenant_id',
        'slug',
        'title',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function theme()
    {
        return $this->hasOne(PublicPageTheme::class, 'page_id');
    }

    protected static function booted(): void
    {
        // Don't apply global scope here automatically since public unauthenticated 
        // users need to find it by slug. But we still enforce tenant_id on creation.
        static::creating(function ($model) {
            if (!$model->tenant_id && auth()->check()) {
                $model->tenant_id = (app()->bound('currentTenant') ? app('currentTenant')->id : auth()->id());
            }
        });
    }
}
