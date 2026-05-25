<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\WhatsAppBillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class AdminWhatsAppBalanceController extends Controller
{
    public function __construct(
        protected WhatsAppBillingService $billingService
    ) {}

    public function index()
    {
        $users = User::withCount(['whatsappMessages', 'whatsappChannels'])
                     ->orderBy('whatsapp_balance_egp', 'desc')
                     ->paginate(20);

        $stats = [
            'total_users'         => User::count(),
            'users_with_balance'  => User::where('whatsapp_balance_egp', '>', 0)->count(),
            'users_needing_reset' => User::where(function ($q) {
                $q->whereNull('whatsapp_balance_reset_date')
                  ->orWhere('whatsapp_balance_reset_date', '<=', now()->subMonths(6));
            })->count(),
            'total_balance'       => User::sum('whatsapp_balance_egp'),
        ];

        return Inertia::render('Admin/WhatsappBalance/Index', [
            'users' => $users,
            'stats' => $stats,
        ]);
    }

    public function show(User $user)
    {
        $whatsappBalanceInfo = $this->billingService->getWhatsAppBalanceInfo($user);
        $messageStats = $this->billingService->getUserStats($user);
        $batchStats = $this->billingService->getDailyBatchStats($user);

        return Inertia::render('Admin/WhatsappBalance/Show', [
            'user'                => $user,
            'whatsappBalanceInfo' => $whatsappBalanceInfo,
            'messageStats'        => $messageStats,
            'batchStats'          => $batchStats,
        ]);
    }

    public function addBalance(Request $request, User $user)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01|max:1000',
            'reason' => 'nullable|string|max:255',
        ]);

        $oldBalance = $user->whatsapp_balance_egp;
        $newBalance = $this->billingService->addWhatsAppBalance($user, $request->amount);

        Log::info("Admin added WhatsApp balance", [
            'admin_id'     => auth()->id(),
            'user_id'      => $user->id,
            'amount_added' => $request->amount,
            'old_balance'  => $oldBalance,
            'new_balance'  => $newBalance,
            'reason'       => $request->reason
        ]);

        return redirect()->back()->with('success', "Added {$request->amount} EGP to WhatsApp balance. New balance: {$newBalance} EGP");
    }

    public function resetBalance(Request $request, User $user)
    {
        $request->validate([
            'amount' => 'nullable|numeric|min:0|max:1000',
            'reason' => 'nullable|string|max:255',
        ]);

        $oldBalance = $user->whatsapp_balance_egp;
        $newAmount = $request->amount ?? 100.00;

        $user->resetWhatsAppBalance($newAmount);

        Log::info("Admin reset WhatsApp balance", [
            'admin_id'    => auth()->id(),
            'user_id'     => $user->id,
            'old_balance' => $oldBalance,
            'new_balance' => $newAmount,
            'reason'      => $request->reason
        ]);

        return redirect()->back()->with('success', "Reset WhatsApp balance to {$newAmount} EGP");
    }

    public function bulkReset(Request $request)
    {
        $users = User::where(function ($q) {
            $q->whereNull('whatsapp_balance_reset_date')
              ->orWhere('whatsapp_balance_reset_date', '<=', now()->subMonths(6));
        })->get();

        $resetCount = 0;
        $totalOldBalance = 0;

        foreach ($users as $user) {
            $totalOldBalance += $user->whatsapp_balance_egp;
            $user->resetWhatsAppBalance(100.00);
            $resetCount++;
        }

        Log::info("Admin bulk reset WhatsApp balances", [
            'admin_id'          => auth()->id(),
            'users_reset'       => $resetCount,
            'total_old_balance' => $totalOldBalance,
            'total_new_balance' => $resetCount * 100
        ]);

        return redirect()->back()->with('success', "Reset WhatsApp balances for {$resetCount} users.");
    }
}
