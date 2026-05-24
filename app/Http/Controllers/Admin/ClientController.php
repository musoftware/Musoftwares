<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Wallet;
use Inertia\Inertia;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $clients = $query->paginate(15)->withQueryString();

        // Construct a mock wallet object on the fly using user_balance and preferred_currency
        $clients->getCollection()->transform(function ($client) {
            $client->wallet = (object)[
                'id' => $client->id,
                'balance' => (float)$client->user_balance,
                'currency' => $client->preferred_currency ?: 'USD',
                'context' => 'Platform Balance',
            ];
            return $client;
        });

        return Inertia::render('Admin/Clients/Index', [
            'clients' => $clients,
            'filters' => [
                'search' => $request->input('search', ''),
            ],
        ]);
    }

    public function show($id)
    {
        $client = User::findOrFail($id);
        
        // Mock wallet for frontend, using user_balance, preferred_currency and transactions list
        $wallets = [
            (object)[
                'id' => $client->id,
                'context' => 'Platform Balance',
                'balance' => (float) $client->user_balance,
                'currency' => $client->preferred_currency ?: 'USD',
                'transactions' => $client->transactions()
                    ->latest()
                    ->take(50)
                    ->get()
                    ->map(function ($tx) {
                        return (object)[
                            'id' => $tx->id,
                            'type' => $tx->type === 'received' ? 'credit' : ($tx->type === 'refunded' ? 'refund' : 'debit'),
                            'amount' => (float) abs($tx->amount),
                            'currency' => $tx->currency_id ?: 'USD',
                            'balance_before' => null,
                            'balance_after' => null,
                            'description' => $tx->reason,
                            'created_at' => $tx->created_at ? $tx->created_at->toIso8601String() : null,
                        ];
                    })
            ]
        ];

        return Inertia::render('Admin/Clients/Show', [
            'client' => $client,
            'wallets' => $wallets,
        ]);
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        
        // Retrieve currencies and plans safely (they might not exist in V2 yet, but we copied models)
        $currencies = class_exists(\App\Models\Currency::class) ? \App\Models\Currency::all() : [];
        $plans = class_exists(\App\Models\Misc\Plan::class) ? \App\Models\Misc\Plan::all() : [];

        return Inertia::render('Admin/Clients/Edit', [
            'user' => $user,
            'currencies' => $currencies,
            'plans' => $plans,
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'full_name' => 'nullable|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $id,
            'password' => 'nullable|string|min:8',
            'facebook' => 'nullable|string|max:255',
            'skype' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:255',
            'phone_number2' => 'nullable|string|max:255',
            'whatsapp_number' => 'nullable|string|max:255',
            'disable_unpaid_balance_whatsapp' => 'nullable|boolean',
            'job' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'hour_rate_currency' => 'nullable|integer',
            'hour_rate' => 'nullable|numeric',
            'booking_rate_currency' => 'nullable|integer',
            'booking_rate' => 'nullable|numeric',
            'booking_rate_expires_at' => 'nullable|date',
            'salary' => 'nullable|numeric',
            'usd_type' => 'nullable|string|max:255',
            'currency' => 'nullable|integer',
            'subscription_date' => 'nullable|date',
            'subscription_plan' => 'nullable|integer',
            'postpaid_limit' => 'nullable|numeric',
            'subscription_force' => 'nullable|boolean',
            'client_taxable' => 'nullable|boolean',
            'invoice_taxable' => 'nullable|boolean',
            'timer_taxable' => 'nullable|boolean',
            'allow_referral_system' => 'nullable|boolean',
            'allow_view_times' => 'nullable|boolean',
            'allow_postpaid' => 'nullable|boolean',
            'kyc_verified' => 'nullable|boolean',
            'kyc_notes' => 'nullable|string',
            'affiliate_commission_percentage' => 'nullable|numeric',
            'add_commission_to_total' => 'nullable|boolean',
            'ref_user_id' => 'nullable|integer',
            'permission' => 'nullable|string',
            'account_status' => 'nullable|string',
            'block_reason' => 'nullable|string',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        } else {
            unset($validated['password']);
        }

        // Map frontend plan_id correctly if needed
        if (array_key_exists('subscription_plan', $validated)) {
            $validated['plan_id'] = $validated['subscription_plan'];
            unset($validated['subscription_plan']);
        }

        $user->update($validated);

        // Handle permissions
        if (!empty($request->permission) && method_exists($user, 'syncRoles')) {
            $user->syncRoles([$request->permission]);
        }

        return redirect()->route('admin.clients.show', $user->id)->with('success', 'User updated successfully.');
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
            'type' => 'required|in:credit,debit,refund',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:500'
        ]);

        $user = User::findOrFail($id);

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($request, $user) {
                $amount = (float)$request->amount;
                $fee = (float)($request->fee ?? 0);
                
                $isCredit = in_array($request->type, ['credit', 'refund']);
                $isUsed = $request->boolean('is_used');
                
                if (!$isCredit && $user->user_balance < $amount) {
                    throw new \Exception('Insufficient wallet balance.');
                }

                $txType = $request->type === 'credit' ? 'received' : ($request->type === 'refund' ? 'refunded' : 'sent');
                $txAmount = $isCredit ? $amount : -$amount;

                $user->add_balance($txAmount, $request->description, $txType, $user->currency_id);

                // Fee processing via CostTransaction (legacy)
                if ($fee > 0) {
                    \App\Models\CostTransaction::add_cost_balance(
                        $user,
                        $fee,
                        'Fee for: ' . $request->description,
                        $user->currency_id
                    );
                }

                // Auto consume balance (is_used)
                if ($isCredit && $isUsed) {
                    $user->add_balance(-$amount, ($request->description ?? 'Used balance') . ' (Auto Used)', 'used', $user->currency_id);
                }

                if ($isCredit) {
                    event(new \App\Events\AmountReceived($user, $amount, $request->description, $user->preferred_currency ?: 'USD'));
                }
            });

            return back()->with('success', 'Transaction recorded successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }
    }

    public function storeTask(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        $task = \App\Models\Task::create([
            'user_id' => $id,
            'task_name' => $request->title,
            'task_description' => $request->description,
            // Assuming no default swimlane or project for a direct client task
        ]);

        return back()->with('success', 'Task created successfully.');
    }

    public function swapBudget(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'target_user_id' => 'required|exists:users,id'
        ]);

        $sourceUser = \App\Models\User::findOrFail($id);
        $targetUser = \App\Models\User::findOrFail($request->target_user_id);
        $amount = (float)$request->amount;

        \Illuminate\Support\Facades\DB::transaction(function () use ($sourceUser, $targetUser, $amount) {
            // Debit source
            $itemDebit = [
                'amount' => $amount,
                'reason' => 'Transfer to user #' . $targetUser->id,
                'fee' => 0,
                'is_used' => false
            ];
            \App\Helpers\TimerHelper::instance()->addSend(request(), $sourceUser, null, $itemDebit);

            // Credit target
            $itemCredit = [
                'amount' => $amount,
                'reason' => 'Transfer from user #' . $sourceUser->id,
                'fee' => 0,
                'is_used' => false
            ];
            \App\Helpers\TimerHelper::instance()->addNoTimerReceived(request(), $targetUser, null, $itemCredit);
        });

        return back()->with('success', 'Budget swapped successfully.');
    }

    public function activateMembership(Request $request, $id)
    {
        $request->validate([
            'plan_id' => 'required'
        ]);

        // Logic for plan attachment using old system
        // Typically $user->plan_id = $request->plan_id
        $user = \App\Models\User::findOrFail($id);
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'plan_id')) {
            $user->update(['plan_id' => $request->plan_id]);
        }

        return back()->with('success', 'Membership activated successfully.');
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
