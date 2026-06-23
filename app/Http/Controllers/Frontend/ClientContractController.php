<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClientContractController extends Controller
{
    /**
     * Show the contract publicly via UUID.
     */
    public function show($uuid)
    {
        $contract = Contract::with(['project', 'versions', 'invoices' => function($q) {
            $q->orderBy('created_at', 'asc');
        }])->where('uuid', $uuid)->firstOrFail();

        // Convert the model to an array and structure the data needed for the view
        $data = [
            'contract' => [
                'id' => $contract->id,
                'uuid' => $contract->uuid,
                'project_name' => $contract->project_name,
                'description' => $contract->description,
                'payment_terms' => $contract->payment_terms,
                'total_amount' => $contract->total_amount,
                'currency' => $contract->currencyRow() ?? \App\Models\Currency::first(),
                'duration' => $contract->duration,
                'status' => $contract->status,
                'signed_at' => $contract->signed_at,
                'content' => $contract->content,
            ],
            'invoices' => $contract->invoices->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'uuid' => $invoice->uuid,
                    'status' => $invoice->status,
                    'total_str' => $invoice->total_str(),
                    'unpaid_str' => $invoice->unpaid_str(),
                    'enc_id' => $invoice->enc_id(),
                    'items' => $invoice->items->map(function($item) {
                        return [
                            'item' => $item->item,
                            'price' => $item->price
                        ];
                    })
                ];
            }),
            'project' => $contract->project ? [
                'id' => $contract->project->id,
                'date_start' => $contract->project->date_start_str(),
                'date_end' => $contract->project->date_end_str(),
            ] : null,
        ];

        return Inertia::render('Frontend/Contract/Show', $data);
    }

    /**
     * Sign the contract
     */
    public function sign(Request $request, $uuid)
    {
        $request->validate([
            'signature' => 'required|string',
            'client_name' => 'required|string'
        ]);

        $contract = Contract::where('uuid', $uuid)->firstOrFail();
        
        if ($contract->status === 'signed') {
            return redirect()->back()->with('error', __('general.already_signed'));
        }

        $contract->client_signature = $request->signature;
        $contract->client_name = $request->client_name;
        $contract->signed_at = now();
        $contract->status = 'signed';
        $contract->save();

        return redirect()->back()->with('success', __('general.contract_signed_successfully'));
    }
}
