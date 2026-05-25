<?php

namespace Modules\AffiliatePos\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\AffiliatePos\Traits\HasStock;

class ProductSku extends Model
{
    use SoftDeletes, HasStock;

    protected $table = 'affiliate_pos_product_skus';
    protected $guarded = [];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function skuValues()
    {
        return $this->hasMany(SkuValue::class, 'sku_id', 'id');
    }

    public function refresh_status()
    {
        $this->status = $this->product->status;
        $this->save();
    }

    public function change_stock($stock)
    {
        $this->setStock($stock);
        $this->status = 'active';
        $this->save();
    }

    public function decrease_stock($stock, $arr = [])
    {
        $this->decreaseStock($stock, $arr);
        $this->status = 'active';
        $this->save();
    }

    public function increase_stock($stock, $arr = [])
    {
        $this->increaseStock($stock, $arr);
        $this->status = 'active';
        $this->save();
    }

    public function commission()
    {
        return $this->product->commission;
    }

    public function price_per_piece()
    {
        return $this->product->price_per_piece();
    }

    public function stock_count(): int
    {
        return $this->stock();
    }

    public function options_as_title(): string
    {
        if (empty($this->title)) {
            $product = $this->product;
            if ($product->type == 'simple') {
                $this->title = $product->name;
            } else {
                $titles = $this->skuValues->map(function ($val) {
                    return $val->optionValue->value ?? '';
                })->filter()->toArray();
                
                $this->title = $product->name . ' ' . implode(', ', $titles);
            }
            $this->save();
        }
        return $this->title;
    }
}
