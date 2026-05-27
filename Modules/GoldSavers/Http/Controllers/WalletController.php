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
    public function show(GoldWallet $wallet)
    {
        if ($wallet->user_id !== Auth::id()) {
            abort(403);
        }

        $wallet->load('transactions');

        return Inertia::render('GoldSavers/Wallets/Show', [
            'wallet' => $wallet
        ]);
    }

    public function addTransaction(Request $request, GoldWallet $wallet)
    {
        if ($wallet->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'type' => 'required|in:buy,sell',
            'grams' => 'required|numeric|min:0.01',
            'karat' => 'required|integer',
            'price_per_gram' => 'required|numeric|min:0.01',
            'fees' => 'nullable|numeric|min:0',
            'transaction_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $validated['total_amount'] = ($validated['grams'] * $validated['price_per_gram']) + ($validated['fees'] ?? 0);
        
        $this->walletService->addTransaction($wallet, $validated);

        return redirect()->back()->with('success', 'Transaction added successfully.');
    }
}
