<?php

namespace App\Http\Requests\Admin\Campaign;

use Illuminate\Foundation\Http\FormRequest;

class StoreCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'type' => 'required|in:email,whatsapp,mixed',
            'target_audience' => 'required|string',
        ];
    }
}
