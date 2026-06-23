<?php

namespace App\Http\Requests\Admin\Campaign;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasAnyRole(['admin', 'super_admin', 'Admin', 'superadmin']);
    }

    public function rules(): array
    {
        return [
            'email_subject_en' => 'nullable|string',
            'email_content_en' => 'nullable|string',
            'whatsapp_content_en' => 'nullable|string',
        ];
    }
}
