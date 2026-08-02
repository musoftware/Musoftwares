<?php

namespace App\Services\AI\Tools;

use App\Models\Currency;
use App\Models\CurrenciesExchange;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Str;

class CreateInvoiceTool implements AiToolInterface
{
    public function name(): string
    {
        return 'create_invoice';
    }

    public function description(): string
    {
        return 'Create a commercial invoice for an approved project scope or feature request with the agreed amount and currency.';
    }

    public function parameters(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'amount' => [
                    'type'        => 'number',
                    'description' => 'Agreed numerical invoice amount',
                ],
                'currency' => [
                    'type'        => 'string',
                    'description' => 'ISO 3-letter currency code (e.g. EGP, USD, SAR, EUR)',
                ],
                'description' => [
                    'type'        => 'string',
                    'description' => 'Detailed description of the invoiced scope or deliverables',
                ],
            ],
            'required'   => ['amount', 'description'],
        ];
    }

    public function execute(Project $project, array $arguments): array
    {
        $user     = User::find($project->user_id) ?? User::first();
        $userCurr = $user?->currency_id ?? 1;

        $amount       = (float) ($arguments['amount'] ?? $arguments['amount_usd'] ?? 0.0);
        $currencyCode = !empty($arguments['currency']) ? strtoupper($arguments['currency']) : 'USD';
        $desc         = $arguments['description'] ?? ('Invoice for project #' . $project->id);

        $fromCurr   = Currency::where('currency', $currencyCode)->first();
        $fromCurrId = $fromCurr ? $fromCurr->id : $userCurr;

        $amountInUserCurrency = $amount > 0
            ? CurrenciesExchange::RateToday($amount, $fromCurrId, $userCurr)
            : 0.0;

        $invoice = Invoice::create([
            'uuid'       => (string) Str::uuid(),
            'project_id' => $project->id,
            'user_id'    => $project->user_id,
            'currency'   => $userCurr,
            'status'     => 'unpaid',
            'unpaid'     => $amountInUserCurrency,
            'paid'       => 0,
        ]);

        $project->updateAiContext([
            'current_invoice_id'     => $invoice->id,
            'current_invoice_status' => 'pending',
            'current_stage'          => 'invoice',
        ]);

        return [
            'status'     => 'success',
            'invoice_id' => $invoice->id,
            'amount'     => number_format($amountInUserCurrency, 2),
            'action'     => 'Created Commercial Invoice',
            'detail'     => "Invoice #{$invoice->id}: {$desc} ({$amount} {$currencyCode})",
        ];
    }
}
