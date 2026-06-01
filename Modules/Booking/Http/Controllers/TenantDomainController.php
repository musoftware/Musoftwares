<?php

namespace Modules\Booking\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Booking\Models\TenantDomain;
use Modules\Booking\Services\DomainVerificationService;
use Inertia\Inertia;

class TenantDomainController extends Controller
{
    public function __construct()
    {
        // Enforce the SaaS feature flag
        $this->middleware('feature:booking-custom-domain');
    }

    public function index()
    {
        $domains = TenantDomain::where('tenant_id', Auth::user()->tenant_id)
            ->latest()
            ->paginate(10);
            
        return Inertia::render('Booking/Domains/Index', [
            'domains' => $domains
        ]);
    }

    public function store(Request $request, DomainVerificationService $domainService)
    {
        $request->validate([
            'domain' => 'required|string|max:255|unique:tenant_domains,domain'
        ]);

        // Enforce branch quota via SaaS Metering
        app(\App\Services\MeteredBillingService::class)->incrementUsage('max_custom_domains', 1);

        $domainStr = strtolower(trim($request->domain));
        
        // Basic clean up of URL if user pasted "https://"
        $domainStr = preg_replace('#^https?://#', '', $domainStr);
        $domainStr = preg_replace('#/.*$#', '', $domainStr);

        $tenantDomain = TenantDomain::create([
            'tenant_id' => Auth::user()->tenant_id,
            'domain' => $domainStr,
            'is_verified' => false,
            'ssl_status' => 'pending',
        ]);

        $domainService->generateToken($tenantDomain);

        return back()->with('success', __('general.domain_added_please_verify_ownership'));
    }

    public function verify(Request $request, $id, DomainVerificationService $domainService)
    {
        $tenantDomain = TenantDomain::where('tenant_id', Auth::user()->tenant_id)
            ->findOrFail($id);

        if ($tenantDomain->is_verified) {
            return back()->with('success', __('general.domain_is_already_verified'));
        }

        $verified = $domainService->verifyDnsTxtRecord($tenantDomain);

        if ($verified) {
            return back()->with('success', __('general.domain_verified_successfully_ssl_provisioning_will_begin_shortly'));
        }

        return back()->withErrors(['error' => 'DNS TXT record not found. Please ensure you added the record and wait for propagation.']);
    }

    public function destroy($id)
    {
        $tenantDomain = TenantDomain::where('tenant_id', Auth::user()->tenant_id)
            ->findOrFail($id);
            
        $tenantDomain->delete();

        return back()->with('success', __('general.domain_removed_successfully'));
    }
}
