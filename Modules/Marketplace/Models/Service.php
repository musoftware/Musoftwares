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

    protected $fillable = ['seller_id', 'category_id', 'title', 'description', 'status', 'is_featured'];

    protected $casts = [
        'is_featured' => 'boolean',
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
}
