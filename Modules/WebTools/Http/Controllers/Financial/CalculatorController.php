<?php

namespace Modules\WebTools\Http\Controllers\Financial;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use App\Services\InstapayPayLinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class CalculatorController extends Controller
{
    /**
     * Show the Payment Calculator tool page
     */
    public function index(): \Inertia\Response
    {
        return Inertia::render('WebTools/Financial/Calculator', [
            'input' => null,
            'result' => null,
            'amount_to_pay' => null,
            'calculation_type' => 'visa_master',
            'ninety_percent' => null,
        ]);
    }

    /**
     * Process calculator calculations
     */
    public function process(Request $request): \Inertia\Response
    {
        $result = null;
        $input = null;
        $amount_to_pay = null;
        $calculation_type = null;
        $ninety_percent = null;

        if ($request->isMethod('POST')) {
            $request->validate([
                'balance_egp' => 'required|numeric|min:0',
                'calculation_type' => 'required|in:visa_master,mobile_wallet'
            ]);

            $input = $request->input('balance_egp');
            $calculation_type = $request->input('calculation_type');

            switch ($calculation_type) {
                case 'visa_master':
                    $result = round($this->calculateInstapayWithdrawal((float)$input), 2);
                    $amount_to_pay = round($this->calculateAmountToPayForBalance((float)$input), 2);
                    $ninety_percent = round($result * 0.9, 2);
                    break;

                case 'mobile_wallet':
                    $result = round($this->calculateMobileToWallet((float)$input), 2);
                    $amount_to_pay = round($this->calculateAmountToPayForMobile((float)$input), 2);
                    $ninety_percent = round($result * 0.9, 2);
                    break;
            }
        }

        return Inertia::render('WebTools/Financial/Calculator', compact(
            'result',
            'input',
            'amount_to_pay',
            'calculation_type',
            'ninety_percent'
        ));
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

    private function calculateMobileToWallet(float $amount): float
    {
        $servicePrice = $amount;
        $fixedFee = 2;
        $serviceFee = $servicePrice * 0.025;
        $processingFee = min(15, max(0.5, $servicePrice * 0.003));

        return round($servicePrice - $fixedFee - $serviceFee - $processingFee, 2);
    }

    private function calculateAmountToPayForMobile(float $desiredBalance): float
    {
        $amount = $desiredBalance;
        $processingFee = max(0.5, $amount * 0.003);
        $processingFee = min(15, $processingFee);
        $amount += $processingFee;
        $amount += 2;
        $amount = $amount / (1 - 0.025);

        return round($amount, 2);
    }
}
