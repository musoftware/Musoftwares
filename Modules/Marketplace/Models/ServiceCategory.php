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

    /** Category names are stored in English; the marketplace_ui.categories translations are keyed by the slugged name. */
    public function localizedName(): string
    {
        $key = 'marketplace_ui.categories.'.\Illuminate\Support\Str::slug($this->name);
        $text = __($key);

        return is_string($text) && $text !== $key ? $text : $this->name;
    }

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
