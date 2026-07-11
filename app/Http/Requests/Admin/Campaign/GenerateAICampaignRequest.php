<?php

namespace App\Http\Requests\Admin\Campaign;

use Illuminate\Foundation\Http\FormRequest;

class GenerateAICampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'context' => 'required|string',
            'tone' => 'required|string',
            'type' => 'required|in:email,whatsapp,mixed',
        ];
    }
}
