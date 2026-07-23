<?php

namespace Modules\Marketplace\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Project;
use Modules\Marketplace\Services\CustomProjectService;

class CustomProjectController extends Controller
{
    public function __construct(protected CustomProjectService $customProjectService) {}

    public function index()
    {
        $projects = Project::where('status', 'open')->latest()->paginate(15);
        return response()->json(['projects' => $projects]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'budget' => 'required|numeric|min:1',
            'deadline' => 'nullable|date',
        ]);

        $project = $this->customProjectService->createProject(auth()->user(), $validated);

        if ($request->header('X-Inertia') || !$request->wantsJson()) {
            return back()->with('success', __('general.project_created_successfully'));
        }

        return response()->json(['success' => true, 'project' => $project]);
    }

    public function submitProposal(Request $request, Project $project)
    {
        $validated = $request->validate([
            'price' => 'required|numeric|min:1',
            'delivery_days' => 'required|integer|min:1',
            'proposal_letter' => 'required|string',
        ]);

        try {
            $proposal = $this->customProjectService->submitProposal($project, auth()->user(), $validated);

            if ($request->header('X-Inertia') || !$request->wantsJson()) {
                return back()->with('success', __('general.proposal_submitted_successfully'));
            }

            return response()->json(['success' => true, 'proposal' => $proposal]);
        } catch (\Exception $e) {
            if ($request->header('X-Inertia') || !$request->wantsJson()) {
                return back()->withErrors(['error' => $e->getMessage()]);
            }

            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }

    public function acceptProposal(Request $request, Project $project)
    {
        $validated = $request->validate([
            'freelancer_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:1',
        ]);

        try {
            $contract = $this->customProjectService->acceptProposalAndCreateContract(
                $project,
                (int) $validated['freelancer_id'],
                (float) $validated['amount']
            );

            if ($request->header('X-Inertia') || !$request->wantsJson()) {
                return back()->with('success', __('general.proposal_accepted_successfully'));
            }

            return response()->json(['success' => true, 'contract' => $contract]);
        } catch (\Exception $e) {
            if ($request->header('X-Inertia') || !$request->wantsJson()) {
                return back()->withErrors(['error' => $e->getMessage()]);
            }

            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }
}
