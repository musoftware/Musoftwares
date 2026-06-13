<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ContractVersion;
use App\Models\Project;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use App\Notifications\ContractUpdatedNotification;

class ProjectContractController extends Controller
{
    /**
     * View project contracts
     */
    public function index(Project $project)
    {
        $project->load(['contracts.versions', 'client', 'contracts.invoices']);
        
        return Inertia::render('Admin/Projects/Contracts', [
            'project' => $project,
            'contracts' => $project->contracts,
            'currencies' => \App\Models\Currency::all()
        ]);
    }

    /**
     * Store a new contract for a project.
     */
    public function store(Request $request, Project $project)
    {
        $validated = $request->validate([
            'description' => 'nullable|string',
            'payment_terms' => 'nullable|string',
            'terms' => 'nullable|string',
            'notes' => 'nullable|string',
            'duration' => 'nullable|string',
            'valid_until' => 'nullable|date',
            'total_amount' => 'required|numeric|min:0',
            'currency_id' => 'required|integer',
            'key_features' => 'nullable|array',
            'pricing_items' => 'nullable|array',
        ]);

        $content = [
            'terms' => $validated['terms'] ?? '',
            'notes' => $validated['notes'] ?? '',
            'duration' => $validated['duration'] ?? '',
            'key_features' => $validated['key_features'] ?? [],
            'pricing_items' => $validated['pricing_items'] ?? [],
        ];

        DB::transaction(function () use ($validated, $content, $project) {
            $contract = Contract::create([
                'uuid' => (string) Str::uuid(),
                'project_id' => $project->id,
                'user_id' => Auth::id(),
                'project_name' => $project->project_name,
                'description' => $validated['description'] ?? null,
                'payment_terms' => $validated['payment_terms'] ?? null,
                'total_amount' => $validated['total_amount'],
                'currency_id' => $validated['currency_id'],
                'valid_until' => $validated['valid_until'] ?? null,
                'status' => 'draft',
                'content' => $content,
            ]);

            // Save first version
            ContractVersion::create([
                'contract_id' => $contract->id,
                'user_id' => Auth::id(),
                'description' => $contract->description,
                'payment_terms' => $contract->payment_terms,
                'content' => $contract->content,
                'total_amount' => $contract->total_amount,
            ]);
        });

        if (isset($validated['status']) && $validated['status'] === 'sent' && $project->client) {
            $project->client->notify(new ContractUpdatedNotification($contract));
        }

        return redirect()->back()->with('success', __('general.contract_created_successfully'));
    }

    /**
     * Update an existing contract and save a new version.
     */
    public function update(Request $request, Project $project, Contract $contract)
    {
        $validated = $request->validate([
            'description' => 'nullable|string',
            'payment_terms' => 'nullable|string',
            'terms' => 'nullable|string',
            'notes' => 'nullable|string',
            'duration' => 'nullable|string',
            'valid_until' => 'nullable|date',
            'total_amount' => 'required|numeric|min:0',
            'currency_id' => 'required|integer',
            'key_features' => 'nullable|array',
            'pricing_items' => 'nullable|array',
            'status' => 'nullable|in:draft,sent,signed,active,completed'
        ]);

        $content = [
            'terms' => $validated['terms'] ?? '',
            'notes' => $validated['notes'] ?? '',
            'duration' => $validated['duration'] ?? '',
            'key_features' => $validated['key_features'] ?? [],
            'pricing_items' => $validated['pricing_items'] ?? [],
        ];

        DB::transaction(function () use ($validated, $content, $contract) {
            $contract->update([
                'description' => $validated['description'] ?? null,
                'payment_terms' => $validated['payment_terms'] ?? null,
                'total_amount' => $validated['total_amount'],
                'currency_id' => $validated['currency_id'],
                'valid_until' => $validated['valid_until'] ?? null,
                'status' => $validated['status'] ?? $contract->status,
                'content' => $content,
            ]);

            // Create a new version
            ContractVersion::create([
                'contract_id' => $contract->id,
                'user_id' => Auth::id(),
                'description' => $contract->description,
                'payment_terms' => $contract->payment_terms,
                'content' => $contract->content,
                'total_amount' => $contract->total_amount,
            ]);
        });

        if (isset($validated['status']) && $validated['status'] === 'sent' && $project->client) {
            $project->client->notify(new ContractUpdatedNotification($contract));
        }

        return redirect()->back()->with('success', __('general.contract_updated_successfully'));
    }

    /**
     * Generate an invoice from the contract for a specific milestone amount.
     */
    public function generateInvoice(Request $request, Project $project, Contract $contract)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        DB::transaction(function () use ($request, $project, $contract) {
            $invoice = new Invoice();
            $invoice->uuid = (string) Str::uuid();
            $invoice->user_id = $project->client->id; // The client
            $invoice->project_id = $project->id;
            $invoice->contract_id = $contract->id;
            $invoice->currency_id = $contract->currency_id;
            $invoice->status = 'unpaid';
            $invoice->job_status = 'pending';
            $invoice->save();

            $item = new InvoiceItem();
            $item->invoice_id = $invoice->id;
            $item->item = $request->title;
            $item->description = $request->description;
            $item->qty = 1;
            $item->price = $request->amount;
            $item->save();

            // Setup unpaid total based on items
            $invoice->unpaid = $invoice->total();
            $invoice->save();
        });

        return redirect()->back()->with('success', __('general.invoice_generated'));
    }
}
