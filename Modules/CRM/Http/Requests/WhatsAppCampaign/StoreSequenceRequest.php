<?php
namespace Modules\CRM\Http\Requests\WhatsAppCampaign;
use Illuminate\Foundation\Http\FormRequest;

class StoreSequenceRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return ['name' => 'required|string|max:255', 'description' => 'nullable|string', 'is_active' => 'sometimes|boolean', 'exit_conditions' => 'nullable|array', 'metadata' => 'nullable|array'];
    }
}
