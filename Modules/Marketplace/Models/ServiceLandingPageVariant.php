<?php

namespace Modules\Marketplace\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceLandingPageVariant extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'landing_page_id',
        'variant_name',
        'traffic_split_percentage',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function landingPage()
    {
        return $this->belongsTo(ServiceLandingPage::class, 'landing_page_id');
    }
}
