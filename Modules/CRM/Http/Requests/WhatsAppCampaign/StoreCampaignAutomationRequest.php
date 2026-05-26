<?php
namespace Modules\CRM\Http\Requests\WhatsAppCampaign;
use Illuminate\Foundation\Http\FormRequest;

class StoreCampaignAutomationRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255', 'trigger_event' => 'required|string',
            'trigger_conditions' => 'nullable|array', 'template_id' => 'nullable|exists:crm_wa_campaign_templates,id',
            'audience_id' => 'nullable|exists:crm_wa_campaign_audiences,id', 'account_id' => 'nullable|exists:crm_whatsapp_accounts,id',
            'message_body' => 'nullable|string', 'metadata' => 'nullable|array',
        ];
    }
}
