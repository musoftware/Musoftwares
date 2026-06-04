<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\PointPackage;
use App\Services\PointPurchaseService;
use App\Http\Requests\StoreCustomPointPurchaseRequest;
use App\Http\Requests\StorePackagePointPurchaseRequest;
use App\Helpers\KashierHelper;

class PointPurchaseController extends Controller
{
    protected PointPurchaseService $pointsService;

    public function __construct(PointPurchaseService $pointsService)
    {
        $this->pointsService = $pointsService;
    }

    public function index()
    {
        $user = auth()->user();
        
        $egpCurrency = \App\Models\Currency::where('currency', 'EGP')->first();
        $userCurrencyId = $user->currency;
        $userCurrency = \App\Models\Currency::find($userCurrencyId);
        
        $currencyCode = $userCurrency ? $userCurrency->currency : 'EGP';
        $rate = 1.0;
        
        if ($egpCurrency && $userCurrencyId && $egpCurrency->id != $userCurrencyId) {
            $rate = \App\Models\CurrenciesExchange::RateToday(1, $egpCurrency->id, $userCurrencyId);
        }

        $tiers = $this->pointsService->getTiers();
        foreach ($tiers as &$tier) {
            $tier['price_per_point'] = $tier['price_per_point'] * $rate;
        }

        $quickPackages = $this->pointsService->getQuickPackages();
        foreach ($quickPackages as &$pkg) {
            $pkg['full_price'] = $pkg['full_price'] * $rate;
            $pkg['total_cost'] = $pkg['total_cost'] * $rate;
            $pkg['price_per_point'] = $pkg['price_per_point'] * $rate;
            $pkg['savings'] = $pkg['savings'] * $rate;
        }

        $transactions = $this->pointsService->getUserTransactions($user->id);

        return Inertia::render('Core/Points/Index', [
            'tiers' => $tiers,
            'quickPackages' => $quickPackages,
            'transactions' => $transactions,
            'currency' => $currencyCode,
            'egpToPreferredRate' => $rate,
        ]);
    }

    public function storeWallet(StoreCustomPointPurchaseRequest $request)
    {
        $user = auth()->user();
        $points = (int) $request->points;
        $costInEgp = $this->pointsService->calculateCost($points);

        try {
            $this->pointsService->processWalletPayment($user, $points, $costInEgp);
            return back()->with('success', __('general.points_purchased_successfully_using_wallet_balance'));
        } catch (\Exception $e) {
            if ($e->getMessage() === 'INSUFFICIENT_FUNDS') {
                $paymentDetails = $this->getUserAmountAndCurrency($user, $costInEgp);
                $paymentUrl = KashierHelper::buildPointPurchasePaymentUrl(
                    $paymentDetails['amount'],
                    $user->id,
                    $user->name,
                    $user->email,
                    null,
                    $points,
                    $paymentDetails['currency']
                );
                return Inertia::location($paymentUrl);
            }
            
            return back()->withErrors(['error' => 'An error occurred during payment processing.']);
        }
    }

    public function store(StorePackagePointPurchaseRequest $request)
    {
        $user = auth()->user();
        $package = PointPackage::findOrFail($request->package_id);
        
        try {
            $this->pointsService->processWalletPayment($user, $package->points, $package->price);
            return back()->with('success', __('general.points_purchased_successfully_using_wallet_balance'));
        } catch (\Exception $e) {
            if ($e->getMessage() === 'INSUFFICIENT_FUNDS') {
                $paymentDetails = $this->getUserAmountAndCurrency($user, $package->price);
                $paymentUrl = KashierHelper::buildPointPurchasePaymentUrl(
                    $paymentDetails['amount'],
                    $user->id,
                    $user->name,
                    $user->email,
                    $package->id,
                    $package->points,
                    $paymentDetails['currency']
                );
                return Inertia::location($paymentUrl);
            }
            
            return back()->withErrors(['error' => 'An error occurred during payment processing.']);
        }
    }

    public function success(Request $request)
    {
        return redirect()->route('points.index')->with('success', __('general.payment_successful_thank_you'));
    }

    public function failure(Request $request)
    {
        return redirect()->route('points.index')->withErrors(['error' => __('general.payment_failed_please_try_again')]);
    }

    public function webhook(Request $request)
    {
        \Illuminate\Support\Facades\Log::info('Point Purchase Kashier Webhook received:', $request->all());

        if (\App\Helpers\KashierHelper::validatePayload()) {
            if ($request->input('data.status') === 'SUCCESS') {
                $data = $request->input('data');
                $metadata = $data['metaData'] ?? [];
                if (is_string($metadata)) {
                    $metadata = json_decode($metadata, true) ?: [];
                }

                $userId = $metadata['user_id'] ?? null;
                $trxId = $data['transactionId'] ?? null;
                $amountPaid = floatval($data['amount'] ?? 0);
                $packageId = $metadata['package_id'] ?? null;
                $points = $metadata['points'] ?? 0;

                if ($userId && $trxId && $amountPaid > 0 && $points > 0) {
                    $user = \App\Models\User::find($userId);
                    
                    if ($user) {
                        // Idempotency check
                        $reason = "Points purchase via Kashier online payment (Trx: $trxId)";
                        $alreadyProcessed = \App\Models\Transaction::where('user_id', $user->id)
                            ->where('reason', $reason)
                            ->exists();

                        if (!$alreadyProcessed) {
                            try {
                                \Illuminate\Support\Facades\DB::transaction(function () use ($user, $amountPaid, $reason, $points, $packageId) {
                                    $user->add_balance($amountPaid, $reason, 'received');
                                    
                                    // Deduct balance for points
                                    $user->add_balance(-$amountPaid, 'Purchased ' . $points . ' points via Kashier', 'used');

                                    // Add points
                                    $user->points_balance = ($user->points_balance ?? 0) + $points;
                                    $user->save();

                                    // Log point transaction
                                    \App\Models\PointTransaction::create([
                                        'user_id' => $user->id,
                                        'points' => $points,
                                        'type' => 'purchased',
                                    ]);
                                });
                                \Illuminate\Support\Facades\Log::info("Kashier points purchase processed successfully for User $userId, Points: $points");
                                return response()->json(['status' => 'success', 'message' => 'Points purchase processed successfully']);
                            } catch (\Exception $e) {
                                \Illuminate\Support\Facades\Log::error('Kashier points purchase failed: ' . $e->getMessage());
                            }
                        } else {
                            \Illuminate\Support\Facades\Log::warning("Duplicate Kashier webhook received for Trx $trxId - skipped");
                            return response()->json(['status' => 'success', 'message' => 'Already processed']);
                        }
                    }
                }
            }
            return response()->json(['status' => 'ignored']);
        }

        return response()->json(['error' => 'Invalid webhook signature'], 400);
    }

    private function getUserAmountAndCurrency($user, $amountInEgp)
    {
        $egpCurrency = \App\Models\Currency::where('currency', 'EGP')->first();
        $userCurrencyId = $user->currency;
        $userCurrency = \App\Models\Currency::find($userCurrencyId);
        
        $currencyCode = $userCurrency ? $userCurrency->currency : 'EGP';
        $rate = 1.0;
        
        if ($egpCurrency && $userCurrencyId && $egpCurrency->id != $userCurrencyId) {
            $rate = \App\Models\CurrenciesExchange::RateToday(1, $egpCurrency->id, $userCurrencyId);
        }
        
        return [
            'amount' => round($amountInEgp * $rate, 2),
            'currency' => $currencyCode,
        ];
    }
}
