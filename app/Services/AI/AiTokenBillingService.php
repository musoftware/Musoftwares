<?php

namespace App\Services\AI;

use App\Helpers\BalancesHelper;
use App\Models\Currency;
use App\Models\CurrenciesExchange;
use App\Models\Project;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AiTokenBillingService
{
    /**
     * Bill actual AI token usage to the client's wallet and return details for UI notification.
     */
    public function billUsageWithAmount(Project $project, int $inputTokens, int $outputTokens, string $reason = 'AI Project Manager Interaction'): array
    {
        $user = User::find($project->user_id);
        if (!$user) {
            return ['success' => false, 'amount' => 0, 'currency_symbol' => 'EGP'];
        }

        $inputCost  = ($inputTokens / 1000) * 0.00015;
        $outputCost = ($outputTokens / 1000) * 0.0006;
        $costUsd    = ($inputCost + $outputCost) * 1.5;
        $costUsd    = max(0.001, $costUsd);

        $usdCurrency = Currency::where('currency', 'USD')->first();
        $costInUserCurrency = $costUsd;
        $currencySymbol = 'USD';

        if ($usdCurrency && $user->currency_id && $user->currency_id !== $usdCurrency->id) {
            $costInUserCurrency = CurrenciesExchange::RateToday($costUsd, $usdCurrency->id, $user->currency_id);
            $userCurr = Currency::find($user->currency_id);
            $currencySymbol = $userCurr?->symbol ?? 'EGP';
        } else {
            $userCurr = Currency::find($user->currency_id);
            $currencySymbol = $userCurr?->symbol ?? '$';
        }

        try {
            DB::transaction(function () use ($user, $project, $costInUserCurrency, $reason) {
                Transaction::create([
                    'user_id'     => $user->id,
                    'amount'      => -$costInUserCurrency,
                    'reason'      => $reason . ' (Project: ' . $project->project_name . ')',
                    'category'    => 'other',
                    'type'        => 'used',
                    'project_id'  => $project->id,
                    'currency_id' => $user->currency_id,
                ]);

                $project->update(['last_ai_charged_at' => now('Africa/Cairo')]);
                BalancesHelper::UpdateBalance($user, $project);
            });

            Log::info("[AI Token Billing] Billed {$user->email}: {$costInUserCurrency} ({$costUsd} USD) for {$inputTokens}+{$outputTokens} tokens.");

            return [
                'success'         => true,
                'amount'          => round($costInUserCurrency, 3),
                'currency_symbol' => $currencySymbol,
            ];
        } catch (\Throwable $e) {
            Log::error("[AI Token Billing Error] " . $e->getMessage());
            return [
                'success'         => false,
                'amount'          => 0,
                'currency_symbol' => $currencySymbol,
            ];
        }
    }
}
