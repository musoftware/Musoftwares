<?php

namespace Modules\Marketplace\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceCategory extends Model
{
    use SoftDeletes;

    protected $table = 'marketplace_service_categories';

    protected $fillable = ['name', 'slug', 'description'];

    public function services(): HasMany
    {
        return $this->hasMany(Service::class, 'category_id');
    }

    /**
     * Cleanly resolves a ServiceCategory model from an instance, ID, or slug.
     */
    public static function resolve(self|int|string|null $category): ?self
    {
        if ($category instanceof self) {
            return $category;
        }

        if (is_numeric($category)) {
            return self::find((int) $category);
        }

        if (is_string($category) && trim($category) !== '') {
            $slug = trim($category);
            return self::where('slug', $slug)->first();
        }

        return null;
    }
}
