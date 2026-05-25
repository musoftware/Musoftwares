<?php

namespace Modules\GoldSavers\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\GoldSavers\Models\GoldWallet;
use Modules\GoldSavers\Services\GoldWalletService;
use Illuminate\Support\Facades\Auth;

class WalletController extends Controller
{
    protected $walletService;

    public function __construct(GoldWalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    public function index()
    {
        $wallets = GoldWallet::where('user_id', Auth::id())
            ->with('transactions')
            ->get();

        return Inertia::render('GoldSavers/Wallets/Index', [
            'wallets' => $wallets
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'goal_type' => 'nullable|string',
            'target_grams' => 'nullable|numeric|min:0',
            'target_amount' => 'nullable|numeric|min:0',
        ]);

        $this->walletService->createWallet(
            Auth::id(),
            $validated['name'],
            $validated['goal_type'] ?? 'Investment',
            $validated['target_grams'] ?? 0,
            $validated['target_amount'] ?? 0,
            Auth::user()->tenant_id
        );

        return redirect()->back()->with('success', 'Wallet created successfully.');
    }
}
