<?php

namespace App\Http\Requests\Admin\Sequence;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSequenceStepRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order' => 'required|integer',
            'delay' => 'required|integer',
            'unit' => 'required|in:minute,hour,day',
            'send_email' => 'boolean',
            'send_whatsapp' => 'boolean',
            'email_subject_en' => 'nullable|string',
            'email_content_en' => 'nullable|string',
            'whatsapp_content_en' => 'nullable|string',
        ];
    }
}
