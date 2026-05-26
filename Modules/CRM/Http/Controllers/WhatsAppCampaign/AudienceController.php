<?php

namespace Modules\CRM\Http\Controllers\WhatsAppCampaign;

use App\Http\Controllers\Controller;
use App\Modules\CRMWhatsAppCampaigns\Services\CampaignAudienceResolver;
use Modules\CRM\Http\Requests\WhatsAppCampaign\StoreAudienceRequest;
use Modules\CRM\Http\Resources\WhatsAppCampaign\AudienceResource;
use Modules\CRM\Models\WhatsAppCampaignAudience;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AudienceController extends Controller
{
    public function __construct(
        protected CampaignAudienceResolver $resolver
    ) {}

    public function index()
    {
        $audiences = WhatsAppCampaignAudience::with('creator')
            ->withCount('members')
            ->latest()
            ->paginate(20);

        return Inertia::render('CRM/WhatsAppCampaigns/Audiences/Index', [
            'audiences' => AudienceResource::collection($audiences),
        ]);
    }

    public function store(StoreAudienceRequest $request)
    {
        $audience = WhatsAppCampaignAudience::create(array_merge($request->validated(), [
            'workspace_id' => session('crm_workspace_id'),
            'created_by'   => auth()->id(),
        ]));

        // Resolve members immediately
        $count = $this->resolver->resolve($audience);

        return redirect()->back()->with('success', "Audience created with {$count} members.");
    }

    public function show(WhatsAppCampaignAudience $audience)
    {
        $audience->load('members');

        return Inertia::render('CRM/WhatsAppCampaigns/Audiences/Show', [
            'audience' => (new AudienceResource($audience))->resolve(),
        ]);
    }

    public function update(StoreAudienceRequest $request, WhatsAppCampaignAudience $audience)
    {
        $audience->update($request->validated());
        return redirect()->back()->with('success', 'Audience updated.');
    }

    public function destroy(WhatsAppCampaignAudience $audience)
    {
        $audience->delete();
        return redirect()->back()->with('success', 'Audience deleted.');
    }

    public function preview(Request $request)
    {
        $request->validate([
            'filters'     => 'required|array',
            'source_type' => 'sometimes|string|in:leads,customers',
        ]);

        $count = $this->resolver->preview(
            session('crm_workspace_id'),
            $request->filters,
            $request->source_type ?? 'leads'
        );

        return response()->json(['estimated_size' => $count]);
    }

    public function resolve(WhatsAppCampaignAudience $audience)
    {
        $count = $this->resolver->resolve($audience);
        return redirect()->back()->with('success', "Audience re-resolved with {$count} members.");
    }
}
