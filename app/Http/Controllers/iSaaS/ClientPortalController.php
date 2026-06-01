<?php

namespace App\Http\Controllers\iSaaS;

use App\Http\Controllers\Controller;
use App\Models\Billing\PlatformContract;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClientPortalController extends Controller
{
    /**
     * View the contract publicly via UUID.
     */
    public function showContract($uuid)
    {
        $contract = PlatformContract::where('uuid', $uuid)->firstOrFail();

        return Inertia::render('iSaaS/ClientPortal/ContractView', [
            'contract' => $contract
        ]);
    }

    /**
     * Client signs the contract digitally.
     */
    public function signContract(Request $request, $uuid)
    {
        $contract = PlatformContract::where('uuid', $uuid)->firstOrFail();

        if ($contract->status === 'signed' || $contract->client_signature) {
            return back()->withErrors(['error' => 'This contract has already been signed.']);
        }

        $request->validate([
            'signature_name' => 'required|string|max:255'
        ]);

        $contract->update([
            'client_signature' => $request->signature_name,
            'signed_at' => now(),
            'status' => 'signed'
        ]);

        return redirect()->back()->with('success', __('general.contract_signed_successfully'));
    }
}
