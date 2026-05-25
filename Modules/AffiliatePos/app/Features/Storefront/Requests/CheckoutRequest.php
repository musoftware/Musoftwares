<?php

namespace Modules\AffiliatePos\app\Features\Storefront\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:50',
            'customer_phone2' => 'nullable|string|max:50',
            'customer_email' => 'nullable|email|max:255',
            'customer_address' => 'required|string',
            'customer_city_id' => 'required|integer',
            'customer_governorate_id' => 'required|integer',
            'note_value' => 'nullable|string',
        ];
    }
}
