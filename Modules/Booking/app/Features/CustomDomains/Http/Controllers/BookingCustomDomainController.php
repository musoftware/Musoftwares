<?php

namespace Modules\Booking\app\Features\CustomDomains\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Booking\app\Features\CustomDomains\Models\BookingCustomDomain;
use Modules\Booking\app\Features\CustomDomains\Services\BookingCustomDomainService;
use Modules\Booking\app\Features\CustomDomains\Services\BookingDomainLimitsService;
use Modules\Booking\app\Features\CustomDomains\Services\DomainVerificationService;
use Illuminate\Http\JsonResponse;

class BookingCustomDomainController extends Controller
{
    protected $domainService;
    protected $limitsService;
    protected $verificationService;

    public function __construct(
        BookingCustomDomainService $domainService,
        BookingDomainLimitsService $limitsService,
        DomainVerificationService $verificationService
    ) {
        $this->domainService = $domainService;
        $this->limitsService = $limitsService;
        $this->verificationService = $verificationService;
    }

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id ?? app('currentTenant')?->id;
        
        $domains = BookingCustomDomain::where('tenant_id', $tenantId)->get();

        return response()->json(['data' => $domains]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'domain' => 'required|string|unique:booking_custom_domains,domain'
        ]);

        $tenantId = $request->user()->tenant_id ?? app('currentTenant')?->id;

        if (!$this->limitsService->canAddCustomDomain($tenantId)) {
            return response()->json(['message' => 'Custom domain limit reached or feature not enabled.'], 403);
        }

        try {
            $domain = $this->domainService->createDomain($tenantId, $request->domain);
            return response()->json(['data' => $domain, 'message' => 'Domain added successfully.'], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id ?? app('currentTenant')?->id;
        
        $domain = BookingCustomDomain::where('tenant_id', $tenantId)->findOrFail($id);
        $this->domainService->deleteDomain($domain->id);

        return response()->json(['message' => 'Domain removed successfully.']);
    }

    public function verify(Request $request, $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id ?? app('currentTenant')?->id;
        
        $domain = BookingCustomDomain::where('tenant_id', $tenantId)->findOrFail($id);
        
        $verified = $this->verificationService->verifyOwnership($domain);

        if ($verified) {
            return response()->json(['message' => 'Domain verified successfully.', 'data' => $domain]);
        }

        return response()->json(['message' => 'Domain verification failed. Please check your DNS records.', 'data' => $domain], 400);
    }

    public function setPrimary(Request $request, $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id ?? app('currentTenant')?->id;
        
        $domain = $this->domainService->setPrimary($id, $tenantId);

        return response()->json(['data' => $domain, 'message' => 'Primary domain updated successfully.']);
    }
}
