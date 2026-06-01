<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\ERP\Models\TenantClient;
use Modules\ERP\Models\ReferralEarning;
use Modules\ERP\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ReferralController extends Controller
{
    private function resolveTenantUser()
    {
        $user = Auth::user();
        if (auth('erp_team')->check()) {
            $user = auth('erp_team')->user()?->tenant?->user;
        }
        return $user;
    }

    private function checkAddon()
    {
        $user = $this->resolveTenantUser();
        if (!$user || !$user->hasModuleSubscription('erp-referrals')) {
            abort(403, __('general.upgrade_to_enable_referral_program'));
        }
    }

    public function index()
    {
        $this->checkAddon();
        $user = $this->resolveTenantUser();
        $tenant = Tenant::where('user_id', $user->id)->firstOrFail();
        
        $clients = TenantClient::where('tenant_id', $tenant->id)
            ->with('referrer')
            ->get()
            ->map(function ($client) {
                return [
                    'id' => $client->id,
                    'name' => $client->name,
                    'email' => $client->email,
                    'referral_code' => $client->referral_code,
                    'referrer' => $client->referrer ? [
                        'name' => $client->referrer->name,
                    ] : null,
                ];
            });

        return Inertia::render('ERP/Referrals/Index', [
            'clients' => $clients,
        ]);
    }

    public function tree(TenantClient $client)
    {
        $this->checkAddon();
        $user = $this->resolveTenantUser();
        $tenant = Tenant::where('user_id', $user->id)->firstOrFail();
        
        if ($client->tenant_id !== $tenant->id) {
            abort(403);
        }

        // Load referrals recursively (2 levels supported)
        $client->load('referrals.referrals');

        return Inertia::render('ERP/Referrals/Tree', [
            'client' => $client,
        ]);
    }

    public function earnings()
    {
        $this->checkAddon();
        $user = $this->resolveTenantUser();
        $tenant = Tenant::where('user_id', $user->id)->firstOrFail();

        $earnings = ReferralEarning::where('tenant_id', $tenant->id)
            ->with(['referrer', 'referee', 'currencyModel'])
            ->latest()
            ->paginate(15);

        $earnings->getCollection()->transform(function($earning) {
            return [
                'id' => $earning->id,
                'created_at' => $earning->created_at,
                'amount' => $earning->amount,
                'currency' => $earning->currencyModel?->currency ?? 'USD',
                'status' => $earning->status,
                'client' => [
                    'name' => $earning->referrer?->name ?? 'Unknown',
                ],
                'referred_client' => [
                    'name' => $earning->referee?->name ?? 'Unknown',
                ],
            ];
        });

        return Inertia::render('ERP/Referrals/Earnings', [
            'earnings' => $earnings,
        ]);
    }
}
