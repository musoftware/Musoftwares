<?php

namespace App\Http\Controllers\Client;

use App\Builders\KashierCheckoutBuilder;
use App\Http\Controllers\Controller;
use App\Models\CurrenciesExchange;
use App\Models\Currency;
use App\Models\PartnerClient;
use App\Models\PartnerCreditLease;
use App\Models\PartnerUsageLog;
use App\Models\User;
use App\Services\PartnerGatewayService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class ClientPartnerGatewayController extends Controller
{
    public function __construct(
        protected PartnerGatewayService $gatewayService
    ) {}

    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = auth()->user();

        /** @var PartnerClient|null $client */
        $client = PartnerClient::where('user_id', $user->id)->first();

        $activeLeases = [];
        $usageLogs = [];

        if ($client) {
            $activeLeases = PartnerCreditLease::where('partner_client_id', $client->id)
                ->latest()
                ->limit(15)
                ->get();

            $usageLogs = PartnerUsageLog::where('partner_client_id', $client->id)
                ->latest()
                ->limit(30)
                ->get();
        }

        $userCurrency = Currency::find($user->currency_id) ?? Currency::where('currency', 'USD')->first();

        return Inertia::render('Client/PartnerGateway/Index', [
            'partnerClient' => $client,
            'activeLeases' => $activeLeases,
            'usageLogs' => $usageLogs,
            'userWalletBalance' => (float)$user->user_balance,
            'userCurrency' => $userCurrency ? $userCurrency->currency : 'USD',
            'userCurrencySymbol' => $userCurrency ? $userCurrency->symbol : '$',
        ]);
    }

    /**
     * Recharge partner balance instantly from user account wallet balance.
     */
    public function topUpWallet(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $validated = $request->validate([
            'amount_usd' => ['required', 'numeric', 'min:1', 'max:50000'],
        ]);

        /** @var PartnerClient $client */
        $client = PartnerClient::where('user_id', $user->id)->firstOrFail();

        try {
            $result = $this->gatewayService->topUpFromUserWallet($user, $client, (float)$validated['amount_usd']);

            return redirect()->back()->with(
                'success',
                "Successfully recharged \${$validated['amount_usd']} USD into your Partner Gateway balance."
            );
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Initiate online payment checkout to recharge partner balance.
     */
    public function topUpOnline(Request $request)
    {
        /** @var User $user */
        $user = auth()->user();

        $validated = $request->validate([
            'amount_usd' => ['required', 'numeric', 'min:1', 'max:50000'],
        ]);

        /** @var PartnerClient $client */
        $client = PartnerClient::where('user_id', $user->id)->firstOrFail();

        $amountUsd = (float)$validated['amount_usd'];

        // Convert to EGP for local gateway if needed
        $egpCurrency = Currency::where('currency', 'EGP')->first();
        $usdCurrency = Currency::where('currency', 'USD')->first();

        $amountEgp = $amountUsd;
        if ($egpCurrency && $usdCurrency) {
            $amountEgp = CurrenciesExchange::RateToday($amountUsd, $usdCurrency->id, $egpCurrency->id);
        }

        try {
            $paymentUrl = KashierCheckoutBuilder::make()
                ->forAmount($amountEgp, 'EGP')
                ->forUser($user->id, $user->name, $user->email)
                ->withPrefix('PARTNER_TOPUP_')
                ->withMetadata([
                    'type' => 'partner_topup',
                    'user_id' => $user->id,
                    'partner_client_id' => $client->id,
                    'amount_usd' => $amountUsd,
                ])
                ->setSuccessUrl(route('client.partner-gateway.index'))
                ->setFailureUrl(route('client.partner-gateway.index'))
                ->redirect();

            if ($paymentUrl) {
                return Inertia::location($paymentUrl);
            }
        } catch (\Throwable $e) {
            // If online gateway is in test mode or unavailable, credit test topup or show error
        }

        return redirect()->back()->with('error', 'Online payment gateway is temporarily unavailable. Please use wallet balance or contact support.');
    }

    /**
     * Rotate the client secret key.
     */
    public function regenerateSecret(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        /** @var PartnerClient $client */
        $client = PartnerClient::where('user_id', $user->id)->firstOrFail();

        $client->regenerateSecret();

        return redirect()->back()->with('success', 'Your Partner Secret Key has been regenerated. Please update your environment variables.');
    }
}
