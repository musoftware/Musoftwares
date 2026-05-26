<?php

namespace Modules\CRM\Http\Requests\WhatsApp;

use Illuminate\Foundation\Http\FormRequest;

class AssignConversationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'agent_id' => 'required|integer|exists:users,id',
            'reason'   => 'nullable|string|max:500',
        ];
    }
}
