<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\ERP\Models\Client;
use Modules\ERP\Models\ReferralEarning;

class ReferralController extends Controller
{
    public function index()
    {
        $clients = Client::with('referrer')->get();
        return Inertia::render('ERP/Referrals/Index', [
            'clients' => $clients
        ]);
    }

    public function tree(Client $client)
    {
        // Load nested referrals
        $client->load('referrals.referrals');
        return Inertia::render('ERP/Referrals/Tree', [
            'client' => $client
        ]);
    }

    public function earnings()
    {
        $earnings = ReferralEarning::with(['client', 'referredClient'])
            ->latest()
            ->paginate(10);

        return Inertia::render('ERP/Referrals/Earnings', [
            'earnings' => $earnings
        ]);
    }
}
