<?php

namespace Modules\ERP\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Controller handles addon checks
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'sku' => [
                'nullable',
                'string',
                'max:255',
            ],
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'currency_id' => 'required|exists:currencies,id',
            'stock_quantity' => 'nullable|numeric|min:0',
            'reorder_level' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ];
    }
}
