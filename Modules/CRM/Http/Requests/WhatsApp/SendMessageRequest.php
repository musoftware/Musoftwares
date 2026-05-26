<?php

namespace Modules\CRM\Http\Requests\WhatsApp;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'body'              => 'required_without:media|nullable|string|max:10000',
            'type'              => 'sometimes|string|in:text,image,video,audio,document,template',
            'media'             => 'required_without:body|nullable|file|max:65536',
            'quoted_message_id' => 'nullable|integer|exists:crm_whatsapp_messages,id',
            'template_name'     => 'required_if:type,template|nullable|string|max:255',
            'template_params'   => 'nullable|array',
            'scheduled_at'      => 'nullable|date|after:now',
        ];
    }
}
