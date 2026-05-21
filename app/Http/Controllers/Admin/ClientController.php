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
        $client = User::findOrFail($id);
        $wallets = $client->wallet ? [$client->wallet] : [];

        return Inertia::render('Admin/Clients/Show', [
            'client' => $client,
            'wallets' => $wallets,
        ]);
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        
        // Retrieve currencies and plans safely (they might not exist in V2 yet, but we copied models)
        $currencies = class_exists(\Modules\Core\Models\Currency::class) ? \Modules\Core\Models\Currency::all() : [];
        $plans = class_exists(\Modules\Core\Models\Plan::class) ? \Modules\Core\Models\Plan::all() : [];

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

                $balBefore = $wallet->balance;
                $balAfter = $isCredit ? ($balBefore + $amount) : ($balBefore - $amount);

                $wt = \Modules\Core\Models\WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'type' => $request->type,
                    'amount' => $amount,
                    'currency' => $wallet->currency,
                    'balance_before' => $balBefore,
                    'balance_after' => $balAfter,
                    'description' => $request->description,
                    'created_by' => \Illuminate\Support\Facades\Auth::id(),
                ]);

                // Fee processing via CostTransaction (legacy)
                if ($fee > 0) {
                    \App\Models\Finance\CostTransaction::create([
                        'user_id' => $wallet->owner_id,
                        'amount' => $fee,
                        'currency' => $wallet->currency,
                        'reason' => 'Fee for: ' . $request->description,
                    ]);
                }

                // Auto consume balance (is_used)
                if ($isCredit && $isUsed) {
                    \Modules\Core\Models\WalletTransaction::create([
                        'wallet_id' => $wallet->id,
                        'type' => 'debit',
                        'amount' => $amount,
                        'currency' => $wallet->currency,
                        'balance_before' => $balAfter,
                        'balance_after' => $balAfter - $amount,
                        'description' => ($request->description ?? 'Used balance') . ' (Auto Used)',
                        'created_by' => \Illuminate\Support\Facades\Auth::id(),
                    ]);
                    $balAfter -= $amount;
                }

                $wallet->update(['balance' => $balAfter]);

                $user = \App\Models\User::find($id);
                if ($user && in_array($request->type, ['credit', 'refund'])) {
                    if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'total_paid')) {
                        $user->increment('total_paid', $amount);
                    }
                    if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'user_balance')) {
                        $user->update(['user_balance' => $balAfter]);
                    }
                    
                    event(new \App\Events\AmountReceived($user, $amount, $request->description, $wallet->currency));
                }
            });

            return back()->with('success', 'Wallet transaction recorded successfully.');
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

        $task = \Modules\Core\Models\Task::create([
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
