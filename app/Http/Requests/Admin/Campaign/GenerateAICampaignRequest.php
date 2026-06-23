<?php

namespace App\Http\Requests\Admin\Campaign;

use Illuminate\Foundation\Http\FormRequest;

class GenerateAICampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasAnyRole(['admin', 'super_admin', 'Admin', 'superadmin']);
    }

    public function rules(): array
    {
        return [
            'context' => 'required|string',
            'tone' => 'required|string',
            'type' => 'required|in:email,whatsapp,mixed'
        ];
    }
}
