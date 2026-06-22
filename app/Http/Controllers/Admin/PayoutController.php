<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payout;
use App\Models\User;
use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class PayoutController extends Controller
{
    private function applyFilters($query, Request $request)
    {
        if ($request->filled('client_id')) {
            $query->where('user_id', $request->client_id);
        }
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }
        return $query;
    }

    public function index(Request $request)
    {
        $query = Payout::with(['user', 'project'])->latest();
        $query = $this->applyFilters($query, $request);

        $payouts = $query->paginate($request->input('per_page', 20))->withQueryString();

        $projects = $request->filled('client_id') 
            ? Project::where('user_id', $request->client_id)->get()
            : Project::all();

        return Inertia::render('Admin/Payouts/Index', [
            'payouts' => $payouts,
            'filters' => $request->only(['client_id', 'project_id', 'search', 'per_page']),
            'projects' => $projects,
        ]);
    }

    public function create(Request $request)
    {
        $clientId = $request->input('user') ?? $request->input('client_id');
        $client = User::find($clientId);
        if (!$client) {
            return redirect()->route('admin.payouts.index')
                ->with('error', __('admin.client_not_found'));
        }

        $project = $request->filled('project_id')
            ? Project::where('id', $request->input('project_id'))->first()
            : null;

        $currencyId = $client->currency_id ?? $client->currency;

        DB::transaction(function () use ($client, $project, $currencyId, &$payout) {
            $payout = Payout::create([
                'user_id' => $client->id,
                'project_id' => $project ? $project->id : null,
                'currency_id' => $currencyId,
            ]);
        });

        return redirect()->route('admin.payouts.show', $payout->id)
            ->with('success', __('admin.payout_created'));
    }

    public function show(Payout $payout)
    {
        $payout->load(['user', 'project', 'items']);
        
        return Inertia::render('Admin/Payouts/Show', [
            'payout' => $payout
        ]);
    }

    public function update(Request $request, Payout $payout)
    {
        if ($payout->status === 'paid') {
            return redirect()->back()->with('error', __('admin.cannot_edit_paid_payout'));
        }

        $request->validate([
            'notes' => 'nullable|string',
            'items' => 'array',
            'items.*.description' => 'required|string',
            'items.*.qty' => 'required|numeric|min:1',
            'items.*.amount' => 'required|numeric|min:0',
            'tax' => 'numeric|min:0',
        ]);

        DB::transaction(function () use ($request, $payout) {
            $payout->update([
                'notes' => $request->notes,
                'tax' => $request->tax ?? 0,
            ]);

            $payout->items()->delete(); // Clear old items
            if ($request->has('items')) {
                foreach ($request->items as $itemData) {
                    $payout->items()->create([
                        'description' => $itemData['description'],
                        'qty' => $itemData['qty'],
                        'amount' => $itemData['amount'],
                    ]);
                }
            }

            // Recalculate totals
            $payout->sub_total = $payout->sub_total();
            $payout->total = $payout->total();
            $payout->save();
        });

        return redirect()->back()->with('success', __('admin.payout_updated'));
    }

    public function markPaid(Request $request, Payout $payout)
    {
        try {
            $payout->mark_as_paid();
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', __('admin.payout_marked_paid'));
    }

    public function destroy(Payout $payout)
    {
        if ($payout->status === 'paid') {
            return redirect()->back()->with('error', __('admin.cannot_delete_paid_payout'));
        }
        
        $payout->delete();
        return redirect()->route('admin.payouts.index')->with('success', __('admin.payout_deleted'));
    }
}
