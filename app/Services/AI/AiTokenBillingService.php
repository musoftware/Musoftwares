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
     * Standard model token rates per 1,000,000 tokens in USD.
     */
    protected array $modelRates = [
        'gpt-4o-mini' => ['input' => 0.15, 'output' => 0.60],
        'gpt-4o' => ['input' => 2.50, 'output' => 10.00],
        'gpt-4.5-preview' => ['input' => 75.00, 'output' => 150.00],
        'o3-mini' => ['input' => 1.10, 'output' => 4.40],
        'o1-mini' => ['input' => 1.10, 'output' => 4.40],
        'gemini-2.0-flash' => ['input' => 0.075, 'output' => 0.30],
        'gemini-1.5-flash' => ['input' => 0.075, 'output' => 0.30],
        'gemini-1.5-pro' => ['input' => 1.25, 'output' => 5.00],
        'gemini-2.0-pro' => ['input' => 1.25, 'output' => 5.00],
    ];

    /**
     * Get rates for a specific AI model with fallback to default settings or gpt-4o-mini.
     */
    public function getModelRates(string $model): array
    {
        // First check dynamic rates from admin settings for our primary models
        if (in_array(strtolower($model), ['gpt-4o-mini', 'gemini-2.0-flash'])) {
            $openaiPrice = (float) \App\Models\AdminSettings::GetValue('openai_1m_tokens_price', 0);
            $geminiPrice = (float) \App\Models\AdminSettings::GetValue('gemini_1m_tokens_price', 0);

            if (strtolower($model) === 'gpt-4o-mini' && $openaiPrice > 0) {
                // Apply the flat 1M token price to both input and output for simplicity, or distribute it.
                // Assuming the dynamic price applies flatly:
                return ['input' => $openaiPrice, 'output' => $openaiPrice];
            }

            if (strtolower($model) === 'gemini-2.0-flash' && $geminiPrice > 0) {
                return ['input' => $geminiPrice, 'output' => $geminiPrice];
            }
        }

        $normalized = strtolower(trim($model));
        if (isset($this->modelRates[$normalized])) {
            return $this->modelRates[$normalized];
        }

        // Search partial matches
        foreach ($this->modelRates as $key => $rates) {
            if (str_contains($normalized, $key) || str_contains($key, $normalized)) {
                return $rates;
            }
        }

        return ['input' => 0.15, 'output' => 0.60];
    }

    /**
     * Bill actual AI token usage to the client's wallet based on real model rates.
     */
    public function billUsageWithAmount(
        Project $project,
        int $inputTokens,
        int $outputTokens,
        string $model = 'gpt-4o-mini',
        string $reason = 'AI Project Manager Interaction'
    ): array {
        $user = User::find($project->user_id);
        if (!$user) {
            return ['success' => false, 'amount' => 0, 'currency_symbol' => 'EGP'];
        }

        if ($inputTokens <= 0 && $outputTokens <= 0) {
            return ['success' => true, 'amount' => 0, 'currency_symbol' => 'USD'];
        }

        $rates = $this->getModelRates($model);
        $inputCostUsd  = ($inputTokens / 1000000.0) * $rates['input'];
        $outputCostUsd = ($outputTokens / 1000000.0) * $rates['output'];
        $rawCostUsd    = $inputCostUsd + $outputCostUsd;

        // Optional Markup from Admin Settings (e.g. 0% for exact cost, or 10% markup)
        $markupPct    = (float) (\App\Models\AdminSettings::GetValue('ai_token_markup_pct', 0));
        $markupFactor = 1.0 + (max(0, $markupPct) / 100.0);
        $costUsd      = $rawCostUsd * $markupFactor;

        $usdCurrency = Currency::where('currency', 'USD')->first();
        $userCurrencyId = $user->currency_id;
        $userCurr       = $userCurrencyId ? Currency::find($userCurrencyId) : null;
        $currencySymbol = $userCurr?->symbol ?? $userCurr?->currency ?? 'USD';

        $costInUserCurrency = $costUsd;

        if ($usdCurrency && $userCurrencyId && (int) $userCurrencyId !== (int) $usdCurrency->id) {
            $costInUserCurrency = (float) CurrenciesExchange::RateTodayNoRound($costUsd, $usdCurrency->id, $userCurrencyId);
        }

        // Format amount accurately (keep up to 4 decimal places for precision)
        $finalAmountFormatted = round((float) $costInUserCurrency, 4);

        try {
            if ($finalAmountFormatted > 0) {
                DB::transaction(function () use ($user, $project, $finalAmountFormatted, $reason, $inputTokens, $outputTokens, $model) {
                    Transaction::create([
                        'user_id'     => $user->id,
                        'amount'      => -$finalAmountFormatted,
                        'reason'      => $reason . " ({$inputTokens}in+{$outputTokens}out @ {$model})",
                        'category'    => 'other',
                        'type'        => 'used',
                        'project_id'  => $project->id,
                        'currency_id' => $user->currency_id,
                    ]);

                    $project->update(['last_ai_charged_at' => now('Africa/Cairo')]);
                    BalancesHelper::UpdateBalance($user, $project);
                });
            }

            Log::info("[AI Token Billing] Billed {$user->email}: {$finalAmountFormatted} {$currencySymbol} ({$costUsd} USD) for {$inputTokens}+{$outputTokens} tokens using model {$model}.");

            return [
                'success'         => true,
                'amount'          => $finalAmountFormatted,
                'currency_symbol' => $currencySymbol,
                'input_tokens'    => $inputTokens,
                'output_tokens'   => $outputTokens,
                'model'           => $model,
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
