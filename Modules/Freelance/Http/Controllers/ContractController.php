<?php

namespace Modules\Freelance\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Freelance\Models\Contract;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class ContractController extends Controller
{
    public function show(Request $request, Contract $contract)
    {
        $user = $request->user();
        if ($contract->client_id !== $user->id && $contract->freelancer_id !== $user->id) {
            abort(403);
        }

        $contract->load(['job', 'proposal', 'client', 'freelancer']);
        return Inertia::render('Freelance/Contracts/Show', ['contract' => $contract]);
    }

    public function complete(Request $request, Contract $contract)
    {
        if ($contract->client_id !== $request->user()->id) {
            abort(403);
        }

        DB::transaction(function () use ($contract) {
            $contract->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
            $contract->job->update(['status' => 'completed']);

            // In a real application, handle transferring funds here.
        });

        return back()->with('success', 'Contract marked as completed.');
    }

    public function dispute(Request $request, Contract $contract)
    {
        $user = $request->user();
        if ($contract->client_id !== $user->id && $contract->freelancer_id !== $user->id) {
            abort(403);
        }

        $contract->update(['status' => 'disputed']);
        // Here you would typically notify admins or trigger a dispute resolution process.

        return back()->with('success', 'Contract dispute initiated.');
    }
}
