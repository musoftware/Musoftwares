<?php
namespace Modules\CRM\Http\Requests\WhatsAppCampaign;
use Illuminate\Foundation\Http\FormRequest;

class StoreSequenceStepRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'step_order' => 'required|integer', 'action_type' => 'required|in:send_message,wait,condition,update_lead,add_tag,remove_tag,exit',
            'template_id' => 'nullable|exists:crm_wa_campaign_templates,id', 'message_body' => 'nullable|string',
            'message_type' => 'sometimes|in:text,image,video,document,template', 'delay_minutes' => 'sometimes|integer|min:0',
            'delay_unit' => 'sometimes|in:minutes,hours,days', 'conditions' => 'nullable|array',
            'on_true_step' => 'nullable|integer', 'on_false_step' => 'nullable|integer',
            'skip_if_replied' => 'sometimes|boolean', 'stop_on_reply' => 'sometimes|boolean', 'metadata' => 'nullable|array',
        ];
    }
}
