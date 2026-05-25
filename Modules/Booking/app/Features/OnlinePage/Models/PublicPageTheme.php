<?php

namespace Modules\Booking\app\Features\OnlinePage\Models;

use Illuminate\Database\Eloquent\Model;

class PublicPageTheme extends Model
{
    protected $table = 'booking_public_page_themes';

    protected $fillable = [
        'tenant_id',
        'page_id',
        'primary_color',
        'logo_url',
        'cover_image_url',
        'font_family',
    ];

    public function page()
    {
        return $this->belongsTo(PublicPage::class, 'page_id');
    }
    
    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (!$model->tenant_id && auth()->check()) {
                $model->tenant_id = auth()->user()->tenant_id;
            }
        });
    }
}
