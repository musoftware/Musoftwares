<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Modules\Core\Models\Wallet;
use Inertia\Inertia;

class ClientController extends Controller
{
    public function index()
    {
        $clients = User::paginate(15);

        // Eager load or manually fetch wallets
        // Assuming wallet owner_type is App\Models\User
        $clientIds = $clients->pluck('id');
        $wallets = Wallet::where('owner_type', User::class)
            ->whereIn('owner_id', $clientIds)
            ->get()
            ->keyBy('owner_id');

        $clients->getCollection()->transform(function ($client) use ($wallets) {
            $client->wallet = $wallets->get($client->id);
            return $client;
        });

        return Inertia::render('Admin/Clients/Index', [
            'clients' => $clients,
        ]);
    }

    public function show($id)
    {
        $client = User::with(['supportTickets'])->findOrFail($id);
        $wallets = Wallet::where('owner_type', User::class)
            ->where('owner_id', $id)
            ->with('transactions')
            ->get();

        return Inertia::render('Admin/Clients/Show', [
            'client' => $client,
            'wallets' => $wallets,
        ]);
    }

    public function loginAs($id)
    {
        $user = User::findOrFail($id);
        $token = $user->createToken('admin-impersonation-' . \Illuminate\Support\Facades\Auth::id())->plainTextToken;

        session(['impersonating_user_id' => $user->id, 'impersonated_by' => \Illuminate\Support\Facades\Auth::id()]);

        // Returns JSON for our modal approach
        return response()->json([
            'token' => $token,
            'redirect_url' => '/dashboard',
            'message' => 'Impersonation token generated.'
        ]);
    }

    public function resetPassword($id)
    {
        $user = User::findOrFail($id);
        $newPassword = \Illuminate\Support\Str::random(10);
        $user->password = \Illuminate\Support\Facades\Hash::make($newPassword);
        $user->save();

        return response()->json([
            'new_password' => $newPassword,
            'message' => 'Password reset successfully.'
        ]);
    }

    public function walletTransaction(Request $request, $id)
    {
        $request->validate([
            'wallet_id' => 'required|exists:wallets,id',
            'type' => 'required|in:credit,debit,refund',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:500'
        ]);

        $wallet = \Modules\Core\Models\Wallet::findOrFail($request->wallet_id);
        
        // Ensure wallet belongs to this user
        if ($wallet->owner_id != $id || $wallet->owner_type != User::class) {
            abort(403, 'Unauthorized wallet access');
        }

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($request, $wallet, $id) {
                $amount = (float)$request->amount;
                $fee = (float)($request->fee ?? 0);
                
                $isCredit = in_array($request->type, ['credit', 'refund']);
                $isUsed = $request->boolean('is_used');
                
                if (!$isCredit && $wallet->balance < $amount) {
                    throw new \Exception('Insufficient wallet balance.');
                }

                $user = \App\Models\User::find($id);

                if ($request->type === 'credit') {
                    $item = [
                        'amount' => $amount,
                        'reason' => $request->description ?? 'Manual Wallet Credit',
                        'fee' => $fee,
                        'is_used' => $isUsed
                    ];
                    \App\Helper\TimerHelper::instance()->addNoTimerReceived($request, $user, null, $item);
                } elseif ($request->type === 'refund') {
                    $item = [
                        'amount' => $amount,
                        'reason' => $request->description ?? 'Manual Wallet Refund',
                        'fee' => $fee,
                        'is_used' => $isUsed
                    ];
                    \App\Helper\TimerHelper::instance()->addRefund($request, $user, null, $item);
                } elseif ($request->type === 'debit') {
                    $item = [
                        'amount' => $amount,
                        'reason' => $request->description ?? 'Manual Wallet Debit',
                        'fee' => $fee,
                        'is_used' => false
                    ];
                    \App\Helper\TimerHelper::instance()->addSend($request, $user, null, $item);
                }

                // Update the visual wallet balance based on User model's dynamic balance calculation, or sync it.
                // Since the monolith uses $user->user_balance, we should sync our modular wallet to match it.
                $user->refresh();
                if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'user_balance')) {
                    $wallet->update(['balance' => $user->user_balance]);
                }
            });

            return back()->with('success', 'Wallet transaction recorded successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }
    }

    public function storeTask(Request $request, $id)
    {
        // To be implemented: Create an ERP task for the client
        return response()->json(['message' => 'Task creation logic goes here']);
    }

    public function swapBudget(Request $request, $id)
    {
        // To be implemented: Transfer funds between wallets
        return response()->json(['message' => 'Budget swap logic goes here']);
    }

    public function activateMembership(Request $request, $id)
    {
        // To be implemented: Attach subscription plan
        return response()->json(['message' => 'Membership logic goes here']);
    }

    public function referrals($id)
    {
        $client = User::findOrFail($id);
        return Inertia::render('Admin/Clients/Referrals', [
            'client' => $client,
        ]);
    }

    public function files($id)
    {
        $client = User::findOrFail($id);
        return Inertia::render('Admin/Clients/Files', [
            'client' => $client,
        ]);
    }

    public function reports($id)
    {
        $client = User::findOrFail($id);
        return Inertia::render('Admin/Clients/Reports', [
            'client' => $client,
        ]);
    }
}
