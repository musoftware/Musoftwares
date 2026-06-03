<?php

namespace Modules\Marketplace\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class Service extends Model
{
    use Searchable, SoftDeletes;

    protected $table = 'marketplace_services';

    protected $fillable = [
        'seller_id', 'category_id', 'title', 'description', 'status',
        'tags', 'faq', 'requirements', 'gallery', 'video_url',
        'approved_at', 'approved_by', 'rejected_at', 'rejection_reason',
        'suspended_at', 'suspended_by', 'is_featured'
    ];

    protected $appends = ['cover_image'];

    protected $casts = [
        'is_featured'  => 'boolean',
        'tags'         => 'array',
        'faq'          => 'array',
        'requirements' => 'array',
        'gallery'      => 'array',
    ];

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function category()
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id');
    }

    public function packages()
    {
        return $this->hasMany(ServicePackage::class, 'service_id');
    }

    public function landingPage()
    {
        return $this->hasOne(ServiceLandingPage::class, 'service_id');
    }

    public function orders()
    {
        return $this->hasManyThrough(ServiceOrder::class, ServicePackage::class, 'service_id', 'package_id');
    }

    public function reviews()
    {
        return $this->hasMany(ServiceReview::class, 'service_id');
    }

    public function getCoverImageAttribute()
    {
        if (!empty($this->gallery) && is_array($this->gallery) && isset($this->gallery[0])) {
            return asset('storage/' . $this->gallery[0]);
        }
        return null;
    }
}
