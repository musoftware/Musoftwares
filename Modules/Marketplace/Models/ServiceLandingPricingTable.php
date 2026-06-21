<?php

namespace Modules\Marketplace\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceLandingPricingTable extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'landing_page_id',
        'plan_name',
        'description',
        'price',
        'currency_id',
        'period',
        'features',
        'is_popular',
        'cta_text',
        'cta_link',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'features' => 'array',
        'is_popular' => 'boolean',
    ];

    public function landingPage()
    {
        return $this->belongsTo(ServiceLandingPage::class, 'landing_page_id');
    }

    public function currency()
    {
        return $this->belongsTo(\App\Models\Currency::class, 'currency_id');
    }
}
