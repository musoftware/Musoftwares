<?php

namespace Modules\CRM\Http\Controllers\WhatsAppCampaign;

use App\Http\Controllers\Controller;
use Modules\CRM\app\Features\CRMWhatsAppCampaigns\Services\WhatsAppTemplateRenderer;
use Modules\CRM\Http\Requests\WhatsAppCampaign\StoreTemplateRequest;
use Modules\CRM\Http\Requests\WhatsAppCampaign\PreviewTemplateRequest;
use Modules\CRM\Http\Resources\WhatsAppCampaign\TemplateResource;
use Modules\CRM\Models\WhatsAppCampaignTemplate;
use Inertia\Inertia;

class TemplateController extends Controller
{
    public function __construct(protected WhatsAppTemplateRenderer $renderer) {}

    public function index()
    {
        $templates = WhatsAppCampaignTemplate::with('creator')->latest()->paginate(20);
        return Inertia::render('CRM/WhatsAppCampaigns/Templates/Index', [
            'templates' => TemplateResource::collection($templates),
        ]);
    }

    public function store(StoreTemplateRequest $request)
    {
        $template = WhatsAppCampaignTemplate::create(array_merge($request->validated(), [
            'workspace_id' => session('crm_workspace_id'),
            'created_by'   => auth()->id(),
        ]));

        return redirect()->back()->with('success', __('crm.template_created'));
    }

    public function update(StoreTemplateRequest $request, WhatsAppCampaignTemplate $template)
    {
        $template->update($request->validated());
        return redirect()->back()->with('success', __('crm.template_updated'));
    }

    public function destroy(WhatsAppCampaignTemplate $template)
    {
        $template->delete();
        return redirect()->back()->with('success', __('crm.template_deleted'));
    }

    public function preview(PreviewTemplateRequest $request)
    {
        $rendered = $this->renderer->preview($request->body, $request->sample_data);
        $placeholders = $this->renderer->extractPlaceholders($request->body);
        $errors = $this->renderer->validate($request->body);

        return response()->json([
            'rendered'     => $rendered,
            'placeholders' => $placeholders,
            'errors'       => $errors,
        ]);
    }
}
