<?php

namespace Modules\CRM\Http\Requests\WhatsApp;

use Illuminate\Foundation\Http\FormRequest;

class StoreAutomationRuleRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'           => 'required|string|max:255',
            'type'           => 'required|string|in:auto_reply,away_message,business_hours,routing,assignment,tag,lead_qualification,follow_up',
            'trigger_event'  => 'required|string|max:100',
            'conditions'     => 'nullable|array',
            'conditions.*.field'    => 'required_with:conditions|string',
            'conditions.*.operator' => 'required_with:conditions|string',
            'conditions.*.value'    => 'required_with:conditions',
            'actions'        => 'required|array|min:1',
            'actions.*.type' => 'required|string',
            'is_active'      => 'sometimes|boolean',
            'priority'       => 'sometimes|integer|min:0|max:100',
            'schedule'       => 'nullable|array',
        ];
    }
}
