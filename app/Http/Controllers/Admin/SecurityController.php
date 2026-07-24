<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Security\StoreRateLimitRequest;
use App\Models\BlockedIp;
use App\Models\RateLimit;
use Illuminate\Http\RedirectResponse;

use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class SecurityController extends Controller
{
    public function index(): Response
    {
        $blockedIps = BlockedIp::latest()->get();
        $rateLimits = RateLimit::latest()->get();

        return Inertia::render('Admin/Settings/Security', [
            'blockedIps' => $blockedIps,
            'rateLimits' => $rateLimits,
        ]);
    }

    public function unblockIp(BlockedIp $blockedIp): RedirectResponse
    {
        Cache::forget("blocked_ip:{$blockedIp->ip_address}");
        $blockedIp->delete();

        return redirect()->back()->with('success', __('admin.ip_unblocked_successfully'));
    }

    public function storeRateLimit(StoreRateLimitRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->input('is_active', true);

        RateLimit::create($data);

        return redirect()->back()->with('success', __('admin.rate_limit_created_successfully'));
    }

    public function deleteRateLimit(RateLimit $rateLimit): RedirectResponse
    {
        $rateLimit->delete();

        return redirect()->back()->with('success', __('admin.rate_limit_deleted_successfully'));
    }
}
