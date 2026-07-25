<?php

namespace App\Http\Requests\Admin\MarketplaceOrder;

use Illuminate\Foundation\Http\FormRequest;

class ResolveMarketplaceDisputeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'action' => 'required|in:refund_buyer,release_to_seller',
            'resolution_reason' => 'nullable|string|max:1000',
        ];
    }
}
