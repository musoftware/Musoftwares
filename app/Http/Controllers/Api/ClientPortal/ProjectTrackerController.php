<?php

namespace App\Http\Controllers\Api\ClientPortal;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\LoyaltyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectTrackerController extends Controller
{
    public function __construct(
        protected LoyaltyService $loyaltyService
    ) {}

    /**
     * Get active projects with stage progress and milestones.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $projects = Project::where('user_id', $user->id)
            ->where('archived', 0)
            ->with(['milestones'])
            ->latest()
            ->get()
            ->map(function ($project) {
                return [
                    'id' => $project->id,
                    'name' => $project->project_name,
                    'progress_stage' => $project->progress_stage ?? 'planning',
                    'progress_percentage' => (int) ($project->progress_percentage ?? 0),
                    'is_brief_complete' => (bool) ($project->is_brief_complete ?? false),
                    'brief_details' => $project->brief_details,
                    'milestones' => $project->milestones,
                    'updated_at' => $project->updated_at?->diffForHumans() ?? '-',
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $projects,
        ]);
    }

    /**
     * Show a single project with complete milestone tracker.
     */
    public function show(Request $request, Project $project): JsonResponse
    {
        if ($project->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized access to project.');
        }

        $project->load('milestones');

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $project->id,
                'name' => $project->project_name,
                'progress_stage' => $project->progress_stage ?? 'planning',
                'progress_percentage' => (int) ($project->progress_percentage ?? 0),
                'is_brief_complete' => (bool) ($project->is_brief_complete ?? false),
                'brief_details' => $project->brief_details,
                'milestones' => $project->milestones,
            ],
        ]);
    }

    /**
     * Submit or complete the Project Brief and earn 100 loyalty points automatically.
     */
    public function submitBrief(Request $request, Project $project): JsonResponse
    {
        if ($project->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized access to project.');
        }

        $validated = $request->validate([
            'brief_details' => ['required', 'string', 'min:20'],
        ]);

        $project->brief_details = $validated['brief_details'];
        $project->is_brief_complete = true;
        if ($project->progress_percentage < 25) {
            $project->progress_percentage = 25;
        }
        $project->save();

        $transaction = $this->loyaltyService->awardPoints(
            $request->user(),
            'brief_submitted',
            $project,
            ['project_name' => $project->project_name]
        );

        return response()->json([
            'status' => 'success',
            'message' => $transaction ? 'Project brief submitted! 100 loyalty points awarded.' : 'Project brief updated.',
            'data' => [
                'project' => $project,
                'points_awarded' => $transaction ? 100 : 0,
            ],
        ]);
    }

    /**
     * Client confirms that the project has finished/delivered successfully.
     */
    public function completeProject(Request $request, Project $project): JsonResponse
    {
        if ($project->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized access to project.');
        }

        $validated = $request->validate([
            'feedback' => ['nullable', 'string', 'max:2000'],
            'rating'   => ['nullable', 'integer', 'min:1', 'max:5'],
        ]);

        $project->progress_stage = 'delivered';
        $project->progress_percentage = 100;
        $project->delivered_at = now();
        $project->status = 'closed';

        // Complete any open milestones
        $project->milestones()->update([
            'is_completed' => true,
            'completed_at' => now(),
        ]);

        if (! empty($validated['feedback'])) {
            $stamp = "\n\n[Client Handover Sign-off (" . now()->setTimezone('Africa/Cairo')->format('Y-m-d H:i') . " Cairo)]: " . $validated['feedback'];
            $project->brief_details = ($project->brief_details ?? '') . $stamp;
        }

        $project->save();

        // Award loyalty points for project completion & sign-off
        $transaction = null;
        try {
            $transaction = $this->loyaltyService->awardPoints(
                $request->user(),
                'project_completed',
                $project,
                [
                    'project_name' => $project->project_name,
                    'rating'       => $validated['rating'] ?? 5,
                    'channel'      => 'client_portal',
                ]
            );
        } catch (\Throwable $e) {
            // Graceful fallback
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Project marked as completed and delivered successfully! Thank you for partnering with us.',
            'data'    => [
                'project'        => $project->fresh(['milestones']),
                'points_awarded' => $transaction ? ($transaction->points ?? 150) : 0,
            ],
        ]);
    }
}
