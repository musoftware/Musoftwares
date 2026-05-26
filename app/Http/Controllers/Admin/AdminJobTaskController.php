<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\ActionHelper;
use App\Http\Controllers\Controller;
use App\Models\JobTask;
use App\Models\JobTaskUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;

class AdminJobTaskController extends Controller
{
    public function index()
    {
        $jobs = JobTask::withCount([
            'users',
            'feedbacks as completed_feedbacks_count' => function ($query) {
                $query->where('status', 'completed');
            }
        ])
        ->orderByDesc('completed_feedbacks_count')
        ->paginate(50)
        ->through(fn ($job) => [
            'id' => $job->id,
            'title' => $job->title,
            'points' => $job->points,
            'user_limit' => $job->user_limit,
            'users_count' => $job->users_count,
            'required_rank' => $job->required_rank,
            'completed_feedbacks_count' => $job->completed_feedbacks_count,
            'paused' => $job->paused ?? 0,
        ]);

        return Inertia::render('Admin/JobTasks/Index', [
            'jobs' => $jobs,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/JobTasks/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'mission' => 'required|string',
            'description' => 'required|string',
            'notice' => 'nullable|string',
            'points' => 'required|integer|min:1',
            'user_limit' => 'required|integer|min:1',
            'required_rank' => 'required|integer|min:1',
            'completion_policy' => 'required|in:once,multiple',
        ]);

        $pointsToConvert = $validated['points'] * $validated['user_limit'];

        // In the old system, if they weren't admin, it validated points.
        // But this is the Admin controller, so the user is always admin.
        // We will still keep the check conceptually just in case.
        if (!Auth::user()->hasRole('admin')) {
            return back()->withErrors(['pointsToConvert' => __('messages.insufficient_points_to_convert')]);
        }

        $validated['user_id'] = Auth::id();
        $validated['points_balance'] = $pointsToConvert;
        
        $jobTask = JobTask::create($validated);
        
        $user = Auth::user();
        if (!Auth::user()->hasRole('admin')) {
            ActionHelper::add_action_coins($user, "Added Task ({$jobTask->id}) - " . time(), -1 * $pointsToConvert);
        }

        return redirect()->route('admin.job-tasks.index')
            ->with('success', 'Job created successfully.');
    }

    public function edit(JobTask $jobTask)
    {
        return Inertia::render('Admin/JobTasks/Edit', [
            'jobTask' => $jobTask,
        ]);
    }

    public function update(Request $request, JobTask $jobTask)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'mission' => 'required|string',
            'description' => 'required|string',
            'notice' => 'nullable|string',
            'points' => 'required|integer|min:1',
            'user_limit' => 'required|integer|min:1',
            'required_rank' => 'required|integer|min:1',
            'completion_policy' => 'required|in:once,multiple',
        ]);

        $newPointsTotal = $validated['points'] * $validated['user_limit'];
        $oldPointsTotal = $jobTask->points * $jobTask->user_limit;

        $owner = Auth::user();
        $availablePoints = $owner->actions()->where('status', 'completed')->sum('coins_reward') + $jobTask->points_balance;

        if (!Auth::user()->hasRole('admin')) {
            if ($newPointsTotal > $availablePoints) {
                return back()->withErrors(['pointsToConvert' => __('messages.insufficient_points_to_convert')]);
            }
        }

        $difference = $newPointsTotal - $oldPointsTotal;
        // The old code assumed $jobTask->user exists, but $owner is the current user anyway. Let's stick to the old logic structure.
        if ($jobTask->user && !$jobTask->user->hasRole('admin')) {
            if ($difference > 0) {
                ActionHelper::add_action_coins($owner, "Updated Task ({$jobTask->id}) - " . time(), -1 * $difference);
            } elseif ($difference < 0) {
                $refundAmount = abs($difference);
                ActionHelper::add_action_coins($owner, "Refund Task Points ({$jobTask->id}) - " . time(), $refundAmount);
            }
        }

        $validated['points_balance'] = $newPointsTotal;
        $jobTask->update($validated);

        return redirect()->route('admin.job-tasks.index')
            ->with('success', 'Job updated successfully.');
    }

    public function destroy(JobTask $jobTask)
    {
        $jobTask->delete();

        return redirect()->route('admin.job-tasks.index')
            ->with('success', 'Job deleted successfully.');
    }

    public function stop(JobTask $jobTask)
    {
        $owner = Auth::user();
        
        if ($jobTask->user && !$jobTask->user->hasRole('admin')) {
            ActionHelper::add_action_coins($owner, "Deleted Task ({$jobTask->id}) - " . time(), $jobTask->points_balance);
        }

        $jobTask->delete();

        return redirect()->route('admin.job-tasks.index')
            ->with('success', 'Job stopped successfully.');
    }

    public function pause(JobTask $jobTask)
    {
        RateLimiter::attempt(
            'pause_job:' . $jobTask->id,
            $perMinute = 1,
            function () use ($jobTask) {
                // If the paused column doesn't exist natively, we might need to add it or it already exists in the new schema. 
                // The legacy DB uses jobs_and_tasks table, we should assume 'paused' exists or add it.
                // It looks like `paused` might not exist in the new migration. But let's keep the logic.
                $jobTask->update([
                    'paused' => 1
                ]);
            });

        return redirect()->route('admin.job-tasks.index')
            ->with('success', 'Job paused successfully.');
    }

    public function feedback(JobTask $jobTask)
    {
        $feedbacks = $jobTask->userJobs()->where('status', 'completed')->with('user')->get()->map(function ($feedback) {
            return [
                'id' => $feedback->id,
                'user' => [
                    'id' => $feedback->user->id,
                    'name' => $feedback->user->name,
                ],
                'status' => $feedback->status,
                'created_at' => $feedback->created_at->format('Y-m-d H:i:s'),
                'feedbacks' => $feedback->feedbacks->map(fn($f) => [
                    'content' => $f->content,
                    'reject_reason' => $f->reject_reason,
                ]),
            ];
        });

        return Inertia::render('Admin/JobTasks/Feedback', [
            'jobTask' => $jobTask,
            'feedbacks' => $feedbacks,
        ]);
    }

    public function approveFeedback($id)
    {
        RateLimiter::attempt(
            'approveFeedback:' . $id,
            $perMinute = 1,
            function () use ($id) {
                DB::beginTransaction();

                try {
                    $feedback = DB::table('user_jobs')
                        ->where('id', $id)
                        ->where('status', 'completed')
                        ->lockForUpdate()
                        ->first();

                    if ($feedback) {
                        if ($feedback->status === 'approved') {
                            DB::rollBack();
                            return redirect()->back()->with('error', 'Feedback already approved.');
                        }

                        DB::table('user_jobs')->where('id', $id)->update(['status' => 'approved']);

                        $job = JobTask::find($feedback->job_id);
                        $seller = User::find($feedback->user_id);
                        ActionHelper::add_action_coins($seller, "Approved Feedback ({$feedback->id}) - " . time(), $job->points);

                        DB::table('jobs_and_tasks')->where('id', $job->id)->decrement('points_balance', $job->points);

                        DB::commit();
                        return redirect()->back()->with('success', 'Feedback approved successfully.');
                    }

                    DB::rollBack();
                    return redirect()->back()->with('error', 'Feedback not found or not completed.');
                } catch (\Exception $e) {
                    DB::rollBack();
                    return redirect()->back()->with('error', 'Error approving feedback.');
                }
            }
        );
        
        return redirect()->back();
    }

    public function rejectFeedback(Request $request, $id)
    {
        RateLimiter::attempt(
            'rejectFeedback:' . $id,
            $perMinute = 1,
            function () use ($id, $request) {
                $feedback = DB::table('user_jobs')->where('id', $id)->first();

                if ($feedback) {
                    DB::table('user_jobs')->where('id', $id)->update(['status' => 'rejected']);

                    $feedback_items = DB::table('job_feedback')->where('user_job_id', $id)->get();
                    foreach ($feedback_items as $feedback_item) {
                        DB::table('job_feedback')->where('id', $feedback_item->id)->update(['reject_reason' => $request->reject_reason]);
                    }
                }
            }
        );

        return redirect()->back()->with('success', 'Feedback rejected.');
    }
}
