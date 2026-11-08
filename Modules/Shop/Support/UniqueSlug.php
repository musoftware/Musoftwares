<?php

namespace Modules\Shop\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class UniqueSlug
{
    /**
     * Keeps Arabic letters, because Arabic words in the address help Arabic search results.
     * Deleted rows count too, so an old address is never given to a different product.
     *
     * @param  class-string<Model>  $model
     */
    public static function make(string $model, string $text, ?int $ignoreId = null): string
    {
        $base = Str::limit(Str::slug($text, '-', null), 150, '') ?: 'item-'.Str::lower(Str::random(6));
        $slug = $base;

        for ($suffix = 2; self::isTaken($model, $slug, $ignoreId); $suffix++) {
            $slug = $base.'-'.$suffix;
        }

        return $slug;
    }

    /** @param  class-string<Model>  $model */
    private static function isTaken(string $model, string $slug, ?int $ignoreId): bool
    {
        return $model::withTrashed()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }
}
