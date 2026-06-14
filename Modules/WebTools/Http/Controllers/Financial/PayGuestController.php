<?php

namespace Modules\WebTools\Http\Controllers\Financial;

use App\Events\CalculateReferralRegisteredEvent;
use App\Helper\CurrencyHelper;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use App\Models\CurrenciesExchange;
use App\Models\Currency;
use App\Models\User;
use App\Services\InstapayPayLinkService;
use App\Services\WhatsAppNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;


class PayGuestController extends Controller
{
    /**
     * Show the Pay Guest tool page
     */
    public function index(): \Inertia\Response
    {
        $payGuestCurrencies = Currency::query()
            ->orderByRaw("CASE WHEN currency = 'EGP' THEN 0 WHEN currency = 'USD' THEN 1 ELSE 2 END, currency")
            ->get();

        return Inertia::render('WebTools/Financial/PayGuest', [
            'payGuestCurrencies' => $payGuestCurrencies,
        ]);
    }

    /**
     * Guest signup: create account and send credentials via WhatsApp (no terms, no reCAPTCHA).
     */
    public function signup(Request $request): RedirectResponse
    {
        $allowedIds = Currency::query()->pluck('id')->all();
        if ($allowedIds === []) {
            return redirect()
                ->route('tools.pay-guest')
                ->withErrors(['currency_id' => __('common.currency_options_not_configured')]);
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency_id' => ['required', 'integer', Rule::in($allowedIds)],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'mobile' => ['required', 'string', 'max:20'],
            'referral' => ['nullable', 'string', 'max:64'],
        ]);

        $currency = Currency::find($validated['currency_id']);
        $amountEgp = $this->amountToEgp((float) $validated['amount'], $currency);

        $mobile = trim($validated['mobile']);
        $existingUser = User::where('email', $validated['email'])->first();
        $isNewAccount = false;

        if ($existingUser) {
            // Existing user – just generate a pay link, no new account needed
            $user = $existingUser;
        } else {
            // New user – create account & send credentials
            $isNewAccount = true;
            $plainPassword = Str::password(14, true, true, false);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'currency' => (string) (Currency::where('currency', 'EGP')->value('id') ?? '2'),
                'password' => Hash::make($plainPassword),
                'phone_number' => $mobile,
                'whatsapp_number' => $mobile,
                'kyc_verified' => true,
                'kyc_verified_at' => now(),
            ]);

            $referral = $validated['referral'] ?? $request->session()->get('referral');
            if ($referral !== null && $referral !== '') {
                if (\App\Helper\ReferralHelper::CheckRef($referral)) {
                    event(new CalculateReferralRegisteredEvent($user, $referral, $request->ip()));
                }
            }

            // Send WhatsApp credentials (best effort – never block flow)
            $whatsappSent = false;
            try {
                $loginUrl = route('login');
                $amountLine = strtoupper((string) $currency->currency) === 'EGP'
                    ? __('payment.amount_egp') . ': ' . number_format($amountEgp, 2)
                    : __('general.amount') . ': ' . number_format((float) $validated['amount'], 2) . ' ' . $currency->currency . ' (' . __('payment.equiv') . ' ' . number_format($amountEgp, 2) . ' EGP)';
                $message = __('common.your_musoftwares_account') . "\n\n"
                    . __('common.login_url') . ": {$loginUrl}\n"
                    . __('common.email') . ": {$user->email}\n"
                    . __('general.password') . ": {$plainPassword}\n\n"
                    . $amountLine . "\n"
                    . __('common.log_in_to_continue_payment');

                /** @var WhatsAppNotificationService $whatsapp */
                $whatsapp = app(WhatsAppNotificationService::class);
                $sendResult = $whatsapp->sendMessage($user, $message);
                $whatsappSent = (bool) ($sendResult['success'] ?? false);
            } catch (\Throwable $e) {
                Log::warning('PayGuest signup: WhatsApp send failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $request->session()->put('pay_guest_whatsapp_sent', $whatsappSent);
        }

        $payLink = $this->buildVisaMasterInstapayPayLink($user->id, $amountEgp);
        $request->session()->put('pay_guest_pay_link', $payLink);
        $request->session()->put('pay_guest_pay_user_id', $user->id);
        $request->session()->put('pay_guest_pay_link_until', now()->addMinutes(30));
        $request->session()->put('pay_guest_signup_success', true);
        $request->session()->put('pay_guest_is_new_account', $isNewAccount);

        if (!$isNewAccount) {
            // Existing user — skip WhatsApp flag, show different message
            $request->session()->put('pay_guest_whatsapp_sent', false);
        }

        return redirect()->route('tools.pay-guest.pay-link');
    }

    /**
     * Show payment URL after signup (includes user_id and fee params like the logged-in calculator).
     */
    public function payLink(Request $request): \Inertia\Response|RedirectResponse
    {
        $link = $request->session()->get('pay_guest_pay_link');
        $until = $request->session()->get('pay_guest_pay_link_until');
        $userId = $request->session()->get('pay_guest_pay_user_id');

        if (!$link || !$userId || !$until || now()->isAfter($until)) {
            return redirect()
                ->route('tools.pay-guest')
                ->with('warning', __('common.payment_link_session_expired'));
        }

        $whatsappSent = (bool) $request->session()->get('pay_guest_whatsapp_sent', false);

        return Inertia::render('WebTools/Financial/PayGuestPayLink', [
            'payLink' => $link,
            'userId' => (int) $userId,
            'whatsappSent' => $whatsappSent,
        ]);
    }

    /**
     * JSON: signed /payment/instapay URL for the logged-in user (calculator live updates).
     */
    public function signedPayUrl(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'balance_egp' => 'required|numeric|min:0',
            'you_will_receive' => 'required|numeric|min:0',
        ]);

        $userId = (int) auth()->id();
        if ($userId <= 0) {
            abort(401);
        }

        $a = InstapayPayLinkService::normAmount($validated['amount']);
        $b = InstapayPayLinkService::normAmount($validated['balance_egp']);
        $y = InstapayPayLinkService::normAmount($validated['you_will_receive']);
        $url = InstapayPayLinkService::buildSignedUrl($userId, (float) $a, (float) $b, (float) $y);

        return response()->json(['url' => $url]);
    }

    /**
     * Same query shape as the authenticated calculator (Visa/Master).
     */
    private function buildVisaMasterInstapayPayLink(int $userId, float $balanceEgp): string
    {
        $youReceive = round($this->calculateInstapayWithdrawal($balanceEgp), 2);
        $amountToPay = round($this->calculateAmountToPayForBalance($balanceEgp), 2);

        return \App\Services\InstapayPayLinkService::buildSignedUrl($userId, $amountToPay, $balanceEgp, $youReceive);
    }

    /**
     * Convert entered amount to EGP using system exchange rates (any currency).
     */
    private function amountToEgp(float $amount, ?Currency $currency): float
    {
        if (! $currency) {
            return round($amount, 2);
        }

        $code = strtoupper((string) $currency->currency);
        if ($code === 'EGP') {
            return round($amount, 2);
        }

        $egpId = Currency::where('currency', 'EGP')->value('id');
        if (! $egpId) {
            return $this->amountToEgpViaOpenExchange($amount, $code);
        }

        $converted = CurrenciesExchange::RateToday($amount, $currency->id, $egpId);
        if ($converted > 0 && (float) $converted !== (float) $amount) {
            return round((float) $converted, 2);
        }

        return $this->amountToEgpViaOpenExchange($amount, $code);
    }

    /**
     * Fallback: convert to EGP via OpenExchangeRates (supports all codes).
     */
    private function amountToEgpViaOpenExchange(float $amount, string $fromCode): float
    {
        foreach ([0, -1] as $dayOffset) {
            try {
                $date = date('Y-m-d', strtotime("{$dayOffset} day"));
                $rate = CurrencyHelper::getRate($date, $fromCode, 'EGP');
                if ($rate !== null && (float) $rate > 0) {
                    return round($amount * (float) $rate, 2);
                }
            } catch (\Throwable $e) {
                Log::debug('PayGuest ' . $fromCode . '→EGP fallback: ' . $e->getMessage());
            }
        }

        return round($amount * 50, 2);
    }

    private function calculateInstapayWithdrawal(float $amount): float
    {
        $servicePrice = $amount;
        $fixedFee = 3;
        $serviceFee = $servicePrice * 0.03;
        $processingFee = min(20, max(0.5, $servicePrice * 0.005));

        return round($servicePrice - $fixedFee - $serviceFee - $processingFee, 2);
    }

    private function calculateAmountToPayForBalance(float $desiredBalance): float
    {
        $amount = $desiredBalance;
        $processingFee = max(0.5, $amount * 0.005);
        $processingFee = min(20, $processingFee);
        $amount += $processingFee;
        $amount += 3;
        $amount = $amount / (1 - 0.03);

        return round($amount, 2);
    }
}
