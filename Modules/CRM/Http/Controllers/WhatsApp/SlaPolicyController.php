<?php

namespace Modules\CRM\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller;
use Modules\CRM\Http\Requests\WhatsApp\StoreSlaPolicyRequest;
use Modules\CRM\Models\WhatsAppSlaPolicy;

class SlaPolicyController extends Controller
{
    public function index()
    {
        if (!feature('crm.wa_inbox')) {
            return response()->json(['upgrade_required' => true], 403);
        }

        $policies = WhatsAppSlaPolicy::active()->get();
        return response()->json(['policies' => $policies]);
    }

    public function store(StoreSlaPolicyRequest $request)
    {
        $policy = WhatsAppSlaPolicy::create(array_merge($request->validated(), [
            'workspace_id' => session('crm_workspace_id'),
        ]));

        return response()->json(['policy' => $policy], 201);
    }

    public function show(WhatsAppSlaPolicy $slaPolicy)
    {
        return response()->json(['policy' => $slaPolicy]);
    }

    public function update(StoreSlaPolicyRequest $request, WhatsAppSlaPolicy $slaPolicy)
    {
        $slaPolicy->update($request->validated());
        return response()->json(['policy' => $slaPolicy]);
    }

    public function destroy(WhatsAppSlaPolicy $slaPolicy)
    {
        $slaPolicy->delete();
        return response()->json(['message' => 'SLA policy deleted.']);
    }
}
