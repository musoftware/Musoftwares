<?php

namespace Modules\ERP\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdjustStockRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'change_amount' => 'required|numeric|not_in:0',
            'reason' => 'required|string|max:255',
        ];
    }
}
