<?php

namespace App\Http\Controllers;

use App\Builders\KashierCheckoutBuilder;
use App\Http\Requests\StoreCustomPointPurchaseRequest;
use App\Http\Requests\StorePackagePointPurchaseRequest;
use App\Models\Currency;
use App\Models\KashierCheckout;
use App\Models\PointPackage;
use App\Models\User;
use App\Services\KashierCheckoutFulfillment;
use App\Services\PointPurchaseService;
use App\Traits\ConvertsCurrency;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PointPurchaseController extends Controller
{
    use ConvertsCurrency;

    protected PointPurchaseService $pointsService;

    public function __construct(PointPurchaseService $pointsService)
    {
        $this->pointsService = $pointsService;
    }

    /** Bought credit lives on the rewards page now, next to the earned points. */
    public function index()
    {
        return redirect()->to(self::creditPageUrl());
    }

    private static function creditPageUrl(): string
    {
        return route('loyalty.index').'#credit';
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
                return $this->redirectToKashier($user, $points, $costInEgp, null);
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
                return $this->redirectToKashier($user, (int) $package->points, (float) $package->price, $package->id);
            }

            return back()->withErrors(['error' => 'An error occurred during payment processing.']);
        }
    }

    /**
     * Record what is being bought server-side, then send the user to Kashier.
     * The webhook fulfils this KashierCheckout row; metaData only carries its id.
     */
    private function redirectToKashier(User $user, int $points, float $costInEgp, ?int $packageId)
    {
        $paymentDetails = $this->pointsService->getUserAmountAndCurrency($user, $costInEgp);
        $currencyId = (int) Currency::where('currency', $paymentDetails['currency'])->value('id');

        $checkout = KashierCheckout::open($user, KashierCheckout::PURPOSE_POINTS, (float) $paymentDetails['amount'], $currencyId, [
            'points' => $points,
            'package_id' => $packageId,
        ]);

        $paymentUrl = KashierCheckoutBuilder::make()
            ->forAmount($paymentDetails['amount'], $paymentDetails['currency'])
            ->forUser($user->id, $user->name, $user->email)
            ->withSource(KashierCheckout::PURPOSE_POINTS, 'pts_')
            ->withMetadata(['checkout_id' => $checkout->id])
            ->withRoutes(
                success: route('points.kashier.success'),
                failure: route('points.kashier.failure'),
                webhook: route('points.kashier.webhook')
            )
            ->build();

        return Inertia::location($paymentUrl);
    }

    public function success(Request $request)
    {
        return redirect()->to(self::creditPageUrl())->with('success', __('general.payment_successful_thank_you'));
    }

    public function failure(Request $request)
    {
        return redirect()->to(self::creditPageUrl())->withErrors(['error' => __('general.payment_failed_please_try_again')]);
    }

    public function webhook(Request $request, KashierCheckoutFulfillment $fulfillment)
    {
        return $fulfillment->respondToWebhook($request, KashierCheckout::PURPOSE_POINTS);
    }
}
