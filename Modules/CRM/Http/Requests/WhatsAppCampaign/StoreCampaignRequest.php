<?php

namespace Modules\CRM\Http\Requests\WhatsAppCampaign;

use Illuminate\Foundation\Http\FormRequest;

class StoreCampaignRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'                => 'required|string|max:255',
            'description'         => 'nullable|string',
            'type'                => 'required|in:broadcast,drip,nurture,reactivation,abandoned_cart,follow_up,promotional,transactional',
            'template_id'         => 'nullable|exists:crm_wa_campaign_templates,id',
            'audience_id'         => 'nullable|exists:crm_wa_campaign_audiences,id',
            'account_id'          => 'nullable|exists:crm_whatsapp_accounts,id',
            'account_rotation'    => 'nullable|array',
            'account_rotation.*'  => 'integer|exists:crm_whatsapp_accounts,id',
            'message_body'        => 'nullable|string',
            'message_type'        => 'sometimes|in:text,image,video,document,template',
            'media_url'           => 'nullable|string',
            'buttons'             => 'nullable|array',
            'batch_size'          => 'sometimes|integer|min:1|max:500',
            'batch_delay_seconds' => 'sometimes|integer|min:1|max:300',
            'max_per_minute'      => 'nullable|integer|min:1',
            'max_per_hour'        => 'nullable|integer|min:1',
            'trigger_event'       => 'nullable|string',
            'trigger_conditions'  => 'nullable|array',
            'metadata'            => 'nullable|array',
        ];
    }
}
