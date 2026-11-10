<?php

namespace Modules\Shop\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShopProduct extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category_id', 'name', 'slug', 'description', 'price', 'compare_at_price', 'images',
        'sizes', 'colors', 'in_stock', 'is_active', 'sort_order', 'seo_title', 'seo_description',
        'angazny_id', 'angazny_price', 'angazny_commission', 'angazny_depot', 'angazny_variants', 'angazny_needs_price',
        'angazny_drive_checked_at', 'content_rewritten_at', 'original_name', 'original_description',
        'color_images', 'images_arranged_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'images' => 'array',
        'sizes' => 'array',
        'colors' => 'array',
        'in_stock' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'angazny_price' => 'decimal:2',
        'angazny_commission' => 'decimal:2',
        'angazny_variants' => 'array',
        'angazny_needs_price' => 'boolean',
        'angazny_drive_checked_at' => 'datetime',
        'content_rewritten_at' => 'datetime',
        'color_images' => 'array',
        'images_arranged_at' => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ShopCategory::class, 'category_id');
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function url(): string
    {
        return route('shop.product', $this->slug);
    }

    /** @return list<string> */
    public function imageUrls(): array
    {
        return array_map(fn (string $path) => asset('uploads/'.$path), $this->images ?? []);
    }

    public function mainImageUrl(): ?string
    {
        return $this->imageUrls()[0] ?? null;
    }

    /** @return array<string, string> colour name => address, leaving out photos that were removed */
    public function colorImageUrls(): array
    {
        return collect($this->color_images ?? [])
            ->filter(fn (string $path) => in_array($path, $this->images ?? [], true))
            ->map(fn (string $path) => asset('uploads/'.$path))
            ->all();
    }

    public function imageUrlFor(?string $color): ?string
    {
        return $this->colorImageUrls()[$color ?? ''] ?? $this->mainImageUrl();
    }

    public function hasDiscount(): bool
    {
        return $this->compare_at_price !== null && (float) $this->compare_at_price > (float) $this->price;
    }

    public function discountPercent(): int
    {
        if (! $this->hasDiscount()) {
            return 0;
        }

        return (int) round((1 - (float) $this->price / (float) $this->compare_at_price) * 100);
    }

    public function canBeOrdered(): bool
    {
        return $this->is_active && $this->in_stock && ! $this->angazny_needs_price;
    }

    public function shippingCost(): float
    {
        return (float) config($this->isFromAngazny() ? 'shop.angazny.shipping' : 'shop.shipping_cost');
    }

    public function isFromAngazny(): bool
    {
        return $this->angazny_id !== null;
    }

    /** @return array{size: ?string, color: ?string, stock_id: int, stock: int}|null */
    public function angaznyVariant(?string $size, ?string $color): ?array
    {
        return collect($this->angazny_variants ?? [])
            ->first(fn (array $variant) => $variant['size'] === $size && $variant['color'] === $color);
    }

    /** Angazny stock is counted per colour and size, so the exact choice must be available. */
    public function canSupply(?string $size, ?string $color): bool
    {
        return ! $this->isFromAngazny() || ($this->angaznyVariant($size, $color)['stock'] ?? 0) > 0;
    }

    public function angaznyProfit(): float
    {
        return round((float) $this->price - ((float) $this->angazny_price - (float) $this->angazny_commission), 2);
    }
}
