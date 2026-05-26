<?php
namespace Modules\CRM\Http\Requests\WhatsAppCampaign;
use Illuminate\Foundation\Http\FormRequest;

class CampaignFilterRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return ['status' => 'nullable|in:draft,scheduled,running,paused,completed,failed,cancelled', 'type' => 'nullable|string', 'search' => 'nullable|string|max:255', 'per_page' => 'nullable|integer|min:1|max:100'];
    }
}
