<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlockedIp;
use App\Models\RateLimit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class SecurityController extends Controller
{
    public function index()
    {
        $blockedIps = BlockedIp::latest()->get();
        $rateLimits = RateLimit::latest()->get();

        return Inertia::render('Admin/Settings/Security', [
            'blockedIps' => $blockedIps,
            'rateLimits' => $rateLimits,
        ]);
    }

    public function unblockIp($id)
    {
        $blockedIp = BlockedIp::findOrFail($id);
        Cache::forget("blocked_ip:{$blockedIp->ip_address}");
        $blockedIp->delete();

        return redirect()->back()->with('success', 'IP address unblocked successfully.');
    }

    public function storeRateLimit(Request $request)
    {
        $data = $request->validate([
            'module' => 'required|string|max:255',
            'tenant_id' => 'nullable|integer',
            'ip_address' => 'nullable|ip',
            'max_requests' => 'required|integer|min:1',
            'decay_minutes' => 'required|integer|min:1',
            'is_active' => 'boolean',
        ]);

        $data['is_active'] = $request->input('is_active', true);

        RateLimit::create($data);

        return redirect()->back()->with('success', 'Rate limit created successfully.');
    }

    public function deleteRateLimit($id)
    {
        $limit = RateLimit::findOrFail($id);
        $limit->delete();

        return redirect()->back()->with('success', 'Rate limit deleted successfully.');
    }
}
