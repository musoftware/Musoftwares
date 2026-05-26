<?php
namespace Modules\CRM\Http\Requests\WhatsAppCampaign;
use Illuminate\Foundation\Http\FormRequest;

class StoreTemplateRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255', 'type' => 'sometimes|in:text,image,video,document,template,interactive',
            'body' => 'required|string', 'placeholders' => 'nullable|array',
            'media_url' => 'nullable|string', 'media_mime_type' => 'nullable|string', 'media_filename' => 'nullable|string',
            'header_text' => 'nullable|string|max:255', 'footer_text' => 'nullable|string|max:255',
            'buttons' => 'nullable|array', 'quick_replies' => 'nullable|array',
            'cta_url' => 'nullable|url', 'cta_text' => 'nullable|string|max:255',
            'wa_template_name' => 'nullable|string', 'wa_template_language' => 'nullable|string',
            'wa_template_params' => 'nullable|array', 'category' => 'nullable|in:marketing,utility,transactional',
        ];
    }
}
