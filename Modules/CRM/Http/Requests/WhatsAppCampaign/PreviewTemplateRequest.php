<?php
namespace Modules\CRM\Http\Requests\WhatsAppCampaign;
use Illuminate\Foundation\Http\FormRequest;

class PreviewTemplateRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return ['body' => 'required|string', 'sample_data' => 'nullable|array'];
    }
}
