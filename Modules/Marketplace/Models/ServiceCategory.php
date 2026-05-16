<?php

namespace Modules\Marketplace\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceCategory extends Model
{
    protected $table = 'marketplace_service_categories';

    protected $fillable = ['name', 'slug', 'description'];

    public function services(): HasMany
    {
        return $this->hasMany(Service::class, 'category_id');
    }
}
