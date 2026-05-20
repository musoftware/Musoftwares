<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Modules\ERP\Models\Client;
use Modules\ERP\Models\TenantClient;
use Modules\ERP\Models\ReferralEarning;
use Modules\ERP\Models\Tenant;

class ReferralController extends Controller
{
    private function resolveTenant(): Tenant
    {
        return Tenant::where('user_id', Auth::id())->firstOrFail();
    }

    /**
     * H6 fix: scope to tenant only.
     */
    public function index()
    {
        $tenant  = $this->resolveTenant();
        $clients = TenantClient::with('referrer')
            ->where('tenant_id', $tenant->id)
            ->get();

        return Inertia::render('ERP/Referrals/Index', [
            'clients' => $clients,
        ]);
    }

    /**
     * H6 fix: verify client belongs to tenant before loading tree.
     */
    public function tree(TenantClient $client)
    {
        $tenant = $this->resolveTenant();

        if ($client->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to referral tree.');
        }

        // Load nested referrals (2 levels — deeper would require recursive query)
        $client->load('referrals.referrals');

        return Inertia::render('ERP/Referrals/Tree', [
            'client' => $client,
        ]);
    }

    /**
     * H6 + H7 fix: scope to tenant, use correct relationship names (referrer/referee).
     */
    public function earnings()
    {
        $tenant = $this->resolveTenant();

        // H7 fix: was ['client', 'referredClient'] — wrong names.
        // Correct names defined on ReferralEarning model are referrer() and referee().
        $earnings = ReferralEarning::with(['referrer', 'referee'])
            ->where('tenant_id', $tenant->id)
            ->latest()
            ->paginate(10);

        return Inertia::render('ERP/Referrals/Earnings', [
            'earnings' => $earnings,
        ]);
    }
}
