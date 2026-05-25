<?php

namespace Modules\Booking\app\Features\WhiteLabel\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Booking\app\Features\WhiteLabel\Services\WhiteLabelDomainResolver;
use Modules\Booking\app\Features\WhiteLabel\Models\WhiteLabelDomain;
use Modules\Booking\app\Features\WhiteLabel\Jobs\VerifyDomainDnsJob;

class DomainController extends Controller
{
    private WhiteLabelDomainResolver $resolver;

    public function __construct(WhiteLabelDomainResolver $resolver)
    {
        $this->resolver = $resolver;
    }

    public function index(Request $request)
    {
        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        return response()->json(WhiteLabelDomain::where('tenant_id', $tenantId)->get());
    }

    public function store(Request $request)
    {
        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;

        $validated = $request->validate([
            'domain' => 'required|string|max:255|unique:booking_white_label_domains,domain',
        ]);

        $domain = $this->resolver->addDomain($tenantId, $validated['domain']);

        // Dispatch background verification
        VerifyDomainDnsJob::dispatch($domain->id);

        return response()->json($domain, 201);
    }

    public function destroy(Request $request, int $id)
    {
        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $domain = WhiteLabelDomain::where('tenant_id', $tenantId)->findOrFail($id);
        $domain->delete();

        return response()->json(null, 204);
    }
}
