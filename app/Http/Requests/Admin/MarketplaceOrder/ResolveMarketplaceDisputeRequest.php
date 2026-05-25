<?php

namespace App\Http\Requests\Admin\MarketplaceOrder;

use Illuminate\Foundation\Http\FormRequest;

class ResolveMarketplaceDisputeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => 'required|in:refund_buyer,release_to_seller'
        ];
    }
}
