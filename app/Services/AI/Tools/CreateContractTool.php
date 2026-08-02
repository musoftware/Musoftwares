<?php

namespace App\Services\AI\Tools;

use App\Models\Contract;
use App\Models\Currency;
use App\Models\CurrenciesExchange;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Str;

class CreateContractTool implements AiToolInterface
{
    public function name(): string
    {
        return 'create_contract';
    }

    public function description(): string
    {
        return 'Create an official project contract for the agreed scope and budget. Returns the contract URL for the client to review and sign by paying the 50% deposit.';
    }

    public function parameters(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'total_amount' => [
                    'type'        => 'number',
                    'description' => 'Agreed numerical 100% total project budget',
                ],
                'currency' => [
                    'type'        => 'string',
                    'description' => 'ISO 3-letter currency code (e.g. EGP, USD, SAR, EUR)',
                ],
                'description' => [
                    'type'        => 'string',
                    'description' => 'Detailed description of project deliverables and terms',
                ],
                'features' => [
                    'type'        => 'array',
                    'items'       => ['type' => 'string'],
                    'description' => 'List of agreed feature titles included in this contract',
                ],
            ],
            'required'   => ['total_amount', 'description'],
        ];
    }

    public function execute(Project $project, array $arguments): array
    {
        $user     = User::find($project->user_id) ?? User::first();
        $userCurr = $user?->currency_id ?? 1;

        $totalAmount  = (float) ($arguments['total_amount'] ?? 0.0);
        $currencyCode = !empty($arguments['currency']) ? strtoupper($arguments['currency']) : 'EGP';
        $desc         = $arguments['description'] ?? ('Contract for project #' . $project->id);
        $features     = $arguments['features'] ?? $project->ai_context['pending_features'] ?? [];

        $fromCurr   = Currency::where('currency', $currencyCode)->first();
        $fromCurrId = $fromCurr ? $fromCurr->id : $userCurr;

        $amountInUserCurrency = $totalAmount > 0
            ? CurrenciesExchange::RateToday($totalAmount, $fromCurrId, $userCurr)
            : 0.0;

        $depositAmount = round($amountInUserCurrency * 0.50, 2);

        $contract = Contract::create([
            'uuid'                => (string) Str::uuid(),
            'project_id'          => $project->id,
            'user_id'             => $project->user_id,
            'project_name'        => $project->project_name,
            'project_description' => $desc,
            'reference'           => 'CNT-' . strtoupper(Str::random(8)),
            'prepared_by'         => 'AI Project Manager',
            'total_amount'        => $amountInUserCurrency,
            'deposit_amount'      => $depositAmount,
            'deposit_paid'        => false,
            'currency_id'         => $userCurr,
            'status'              => 'sent',
            'features'            => $features,
            'description'         => $desc,
            'payment_terms'       => '50% upfront deposit required upon contract signature, 50% upon final delivery.',
            'terms'               => 'Standard software agency terms and conditions apply. All IP transferred upon full payment.',
        ]);

        $contractUrl = url('/c/' . $contract->uuid);

        $project->updateAiContext([
            'current_contract_id'   => $contract->id,
            'current_contract_uuid' => $contract->uuid,
            'current_contract_url'  => $contractUrl,
            'current_stage'         => 'PROPOSAL',
        ]);

        return [
            'status'        => 'success',
            'contract_id'   => $contract->id,
            'contract_uuid' => $contract->uuid,
            'contract_url'  => $contractUrl,
            'total_amount'  => number_format($amountInUserCurrency, 2),
            'deposit_amount'=> number_format($depositAmount, 2),
            'action'        => 'Created Official Project Contract',
            'detail'        => "Contract #{$contract->id} created. Send contract_url to client in chat: {$contractUrl}",
        ];
    }
}
