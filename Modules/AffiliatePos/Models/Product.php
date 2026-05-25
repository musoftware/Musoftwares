<?php

namespace Modules\AffiliatePos\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\AffiliatePos\Traits\Taggable;

class Product extends Model
{
    use SoftDeletes, Taggable;

    protected $table = 'affiliate_pos_products';
    protected $guarded = [];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    public function vendor()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class, 'product_id', 'id');
    }

    public function options()
    {
        return $this->hasMany(Option::class, 'product_id', 'id');
    }

    public function option_values()
    {
        return $this->hasMany(OptionValue::class, 'product_id', 'id');
    }

    public function skus()
    {
        return $this->hasMany(ProductSku::class, 'product_id', 'id');
    }

    public function sku_values()
    {
        return $this->hasManyThrough(SkuValue::class, ProductSku::class, 'product_id', 'sku_id', 'id', 'id');
    }

    public function price_per_piece()
    {
        return $this->price + $this->commission;
    }

    public function has_images(): bool
    {
        return $this->images()->count() > 0;
    }

    public function retrieve_skus(): array
    {
        if ($this->type == 'simple') {
            return [$this->generate_sku_simple()];
        } else {
            return $this->generate_sku_variant();
        }
    }

    public function stock_count(): int
    {
        return $this->skus->sum(function ($sku) {
            return $sku->stock();
        });
    }

    public function refresh_skus_status()
    {
        $this->skus()->update(['status' => $this->status]);
    }

    public function generate_sku_simple()
    {
        $sku = $this->skus()->first();
        if (!$sku) {
            $sku = $this->skus()->create([
                'title' => $this->name,
                'status' => $this->status
            ]);
        }
        return $sku;
    }

    public function generate_sku_variant(): array
    {
        // Ported variant generation logic
        $options = $this->options()->with('values')->get();
        if ($options->isEmpty()) return [];
        
        $combinations = [[]];
        foreach ($options as $option) {
            $append = [];
            foreach ($combinations as $combination) {
                foreach ($option->values as $value) {
                    $append[] = array_merge($combination, [$option->id => $value->id]);
                }
            }
            $combinations = $append;
        }

        $createdSkus = [];
        foreach ($combinations as $combo) {
            $createdSkus[] = $this->create_or_find_by_options($combo);
        }
        return $createdSkus;
    }

    public function create_or_find_by_options(array $optionValueIds)
    {
        $skus = $this->skus()->with('skuValues')->get();
        foreach ($skus as $sku) {
            $skuVals = $sku->skuValues->pluck('option_value_id')->toArray();
            if (empty(array_diff($optionValueIds, $skuVals)) && empty(array_diff($skuVals, $optionValueIds))) {
                return $sku;
            }
        }

        $sku = $this->skus()->create(['status' => $this->status]);
        foreach ($optionValueIds as $optId => $valId) {
            $sku->skuValues()->create([
                'option_id' => $optId,
                'option_value_id' => $valId
            ]);
        }
        $sku->title = $sku->options_as_title();
        $sku->save();
        
        return $sku;
    }

    private function isDeleted($exist_options, $id)
    {
        foreach ($exist_options as $exist_option) {
            if (isset($exist_option['id']) && $exist_option['id'] == $id) return false;
        }
        return true;
    }

    public function save_options($saving_product_options)
    {
        // Simple simplified version of option saving logic
        // This will be expanded in the proper Service class later, but keeping interface for compatibility
        foreach ($saving_product_options as $optData) {
            $option = $this->options()->updateOrCreate(
                ['id' => $optData['id'] ?? null],
                ['name' => $optData['option_name']]
            );
            
            if (isset($optData['values'])) {
                foreach ($optData['values'] as $valData) {
                    $option->values()->updateOrCreate(
                        ['id' => $valData['id'] ?? null],
                        ['product_id' => $this->id, 'value' => $valData['value_name']]
                    );
                }
            }
        }
    }
}
