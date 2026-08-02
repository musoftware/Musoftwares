<?php

namespace App\Services\AI\Tools;

use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Str;

class CreateInvoiceTool implements AiToolInterface
{
    public function getName(): string
    {
        return 'create_invoice';
    }

    public function getDescription(): string
    {
        return 'Create a commercial invoice for approved scope or feature request.';
    }

    public function getParametersSchema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'amount_usd' => [
                    'type'        => 'number',
                    'description' => 'Invoice amount in USD',
                ],
                'description' => [
                    'type'        => 'string',
                    'description' => 'Commercial scope description',
                ],
            ],
            'required'   => ['amount_usd', 'description'],
        ];
    }

    public function execute(Project $project, array $arguments): array
    {
        $amountUsd = (float) ($arguments['amount_usd'] ?? 450.0);
        $desc      = $arguments['description'] ?? ('Invoice for ' . $project->name);

        $user     = User::find($project->user_id) ?? User::first();
        $userCurr = $user->currency_id ?? 1;

        $usdCurr = \App\Models\Currency::where('currency', 'USD')->first();
        $amountInUserCurrency = $usdCurr
            ? \App\Models\CurrenciesExchange::RateToday($amountUsd, $usdCurr->id, $userCurr)
            : ($amountUsd * 50);

        $invoice = Invoice::create([
            'uuid'         => (string) Str::uuid(),
            'project_id'   => $project->id,
            'user_id'      => $user->id,
            'currency_id'  => $userCurr,
            'status'       => 'unpaid',
            'description'  => $desc,
            'subtotal'     => $amountInUserCurrency,
            'tax'          => 0,
            'total'        => $amountInUserCurrency,
            'final_total'  => $amountInUserCurrency,
            'unpaid'       => $amountInUserCurrency,
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
            'detail'     => "Invoice #{$invoice->id}: {$desc} ({$amountUsd} USD)",
        ];
    }
}
