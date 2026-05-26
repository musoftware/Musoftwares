<?php

namespace Modules\CRM\Http\Requests\WhatsApp;

use Illuminate\Foundation\Http\FormRequest;

class ConversationFilterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'status'            => 'nullable|string|in:open,pending,resolved,archived',
            'type'              => 'nullable|string|in:lead,support,sales,general',
            'assigned_agent_id' => 'nullable|integer|exists:users,id',
            'unassigned'        => 'nullable|boolean',
            'label_id'          => 'nullable|integer|exists:crm_whatsapp_labels,id',
            'search'            => 'nullable|string|max:500',
            'account_id'        => 'nullable|integer|exists:crm_whatsapp_accounts,id',
            'priority'          => 'nullable|string|in:low,medium,high,urgent',
            'sla_breached'      => 'nullable|boolean',
            'is_pinned'         => 'nullable|boolean',
            'per_page'          => 'nullable|integer|min:10|max:100',
        ];
    }
}
