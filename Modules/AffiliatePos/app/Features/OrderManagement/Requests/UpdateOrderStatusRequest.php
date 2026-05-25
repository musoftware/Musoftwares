<?php

namespace Modules\AffiliatePos\app\Features\OrderManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'status' => 'required|string|in:new,working,printing,preparing,pending,cancelled,shipping,shipped,partial_delivery,returned,replacing,delivered,collected,commission_payed'
        ];
    }
}
