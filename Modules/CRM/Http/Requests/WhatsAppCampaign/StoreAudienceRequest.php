<?php
namespace Modules\CRM\Http\Requests\WhatsAppCampaign;
use Illuminate\Foundation\Http\FormRequest;

class StoreAudienceRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255', 'description' => 'nullable|string',
            'filters' => 'required|array', 'filters.*.field' => 'required|string', 'filters.*.operator' => 'required|string',
            'filters.*.value' => 'required', 'source_type' => 'sometimes|in:leads,customers,contacts,manual,csv',
            'suppression_rules' => 'nullable|array', 'is_dynamic' => 'sometimes|boolean',
        ];
    }
}
