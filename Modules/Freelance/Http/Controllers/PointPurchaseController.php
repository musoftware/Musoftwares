<?php

namespace Modules\Freelance\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Freelance\Models\PointPackage;
use Modules\Freelance\Models\PointTransaction;
use Modules\Core\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\KashierHelper;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class PointPurchaseController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'package_id' => 'required|exists:point_packages,id',
        ]);

        $package = PointPackage::findOrFail($validated['package_id']);
        $user = $request->user();
        $wallet = $user->getWallet();

        // IF wallet balance >= package price: deduct balance
        if ($wallet->balance >= $package->price) {
            DB::transaction(function () use ($user, $wallet, $package) {
                $balanceBefore = $wallet->balance;
                $wallet->balance -= $package->price;
                $wallet->save();

                WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'type' => 'debit',
                    'amount' => $package->price,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $wallet->balance,
                    'reference_type' => 'point_package_purchase',
                    'reference_id' => $package->id,
                    'description' => "Purchased {$package->name} package using wallet balance",
                    'business_amount' => $package->price,
                    'business_currency' => $wallet->currency ?? 'USD',
                ]);

                PointTransaction::create([
                    'user_id' => $user->id,
                    'points' => $package->points,
                    'type' => 'purchased',
                    'description' => "Purchased {$package->name} package via Wallet",
                ]);
            });

            return back()->with('success', "Successfully purchased {$package->points} points from your wallet.");
        }

        // ELSE: redirect to Kashier checkout
        $currency = $package->currency_code === '$' ? 'USD' : ($package->currency_code ?: 'USD');
        $paymentUrl = KashierHelper::buildPointPurchasePaymentUrl(
            (float) $package->price,
            $user->id,
            $user->name,
            $user->email,
            $package->id,
            $package->points,
            $currency
        );

        return Inertia::location($paymentUrl);
    }

    public function storeWallet(Request $request)
    {
        $validated = $request->validate([
            'points' => 'required|integer|min:1',
        ]);

        $points = (int) $validated['points'];
        $cost = round($points * 0.10, 2); // $0.10 per point

        $user = $request->user();
        $wallet = $user->getWallet();

        if ($wallet->balance < $cost) {
            // Also redirect to Kashier if insufficient balance?
            // The prompt says "IF wallet balance >= package price ... ELSE ... redirect to Kashier".
            // Let's implement it for custom points too.
            $paymentUrl = KashierHelper::buildPointPurchasePaymentUrl(
                (float) $cost,
                $user->id,
                $user->name,
                $user->email,
                null,
                $points,
                'USD'
            );
            return Inertia::location($paymentUrl);
        }

        DB::transaction(function () use ($user, $wallet, $points, $cost) {
            $balanceBefore = $wallet->balance;
            $wallet->balance -= $cost;
            $wallet->save();

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'debit',
                'amount' => $cost,
                'balance_before' => $balanceBefore,
                'balance_after' => $wallet->balance,
                'reference_type' => 'point_purchase',
                'reference_id' => $user->id,
                'description' => "Purchased {$points} points using wallet balance",
                'business_amount' => $cost,
                'business_currency' => $wallet->currency ?? 'USD',
            ]);

            PointTransaction::create([
                'user_id' => $user->id,
                'points' => $points,
                'type' => 'purchased',
                'description' => "Purchased {$points} custom points via Wallet",
            ]);
        });

        return back()->with('success', "Successfully purchased {$points} points.");
    }

    public function success(Request $request)
    {
        return redirect()->route('freelance.points.index')->with('success', 'Payment successful! Points have been credited to your account.');
    }

    public function failure(Request $request)
    {
        return redirect()->route('freelance.points.index')->with('error', 'Payment failed or was canceled. Please try again.');
    }

    public function webhook(Request $request)
    {
        Log::info('Kashier Points Webhook received:', $request->all());

        if (KashierHelper::validatePayload()) {
            if ($request->input('data.status') === 'SUCCESS') {
                $data = $request->input('data');
                $metadata = $data['metaData'] ?? [];
                if (is_string($metadata)) {
                    $metadata = json_decode($metadata, true) ?: [];
                }

                $userId = $metadata['user_id'] ?? null;
                $trxId = $data['transactionId'] ?? null;
                $points = $metadata['points'] ?? 0;
                $packageId = $metadata['package_id'] ?? null;
                $source = $metadata['source'] ?? '';

                if ($userId && $trxId && $points > 0 && $source === 'points-purchase') {
                    $user = User::find($userId);
                    if ($user) {
                        $alreadyProcessed = PointTransaction::where('type', 'purchased')
                            ->where('description', 'LIKE', "%(Trx: $trxId)")
                            ->exists();

                        if (!$alreadyProcessed) {
                            DB::transaction(function () use ($user, $points, $trxId, $packageId) {
                                $pkgName = $packageId ? PointPackage::find($packageId)?->name ?? 'Package' : 'Custom Points';
                                
                                PointTransaction::create([
                                    'user_id' => $user->id,
                                    'points' => $points,
                                    'type' => 'purchased',
                                    'description' => "Purchased {$pkgName} via Kashier (Trx: $trxId)",
                                ]);
                                
                                // We also record a ledger transaction in wallet for completeness if required,
                                // but typically points are direct. If user needs it in WalletTransactions, we can add a 0 amount info row,
                                // or just log it in PointTransactions as done above.
                            });

                            Log::info("Kashier points purchase processed successfully for User $userId, Points: $points");
                            return response()->json(['status' => 'success', 'message' => 'Purchase processed successfully']);
                        } else {
                            Log::warning("Duplicate Kashier webhook received for points Trx $trxId - skipped");
                            return response()->json(['status' => 'success', 'message' => 'Already processed']);
                        }
                    }
                }
            }
            return response()->json(['status' => 'ignored']);
        }

        return response()->json(['error' => 'Invalid webhook signature'], 400);
    }
}

