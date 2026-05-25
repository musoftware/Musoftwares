<?php

namespace Modules\AffiliatePos\app\Features\Storefront\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddToCartRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'sku_id' => 'required|exists:affiliate_pos_product_skus,id',
            'quantity' => 'required|integer|min:1',
        ];
    }
}
