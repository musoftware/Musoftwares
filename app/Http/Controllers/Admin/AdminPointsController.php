<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PointTransaction;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminPointsController extends Controller
{
    /**
     * Display all users with their current points balance.
     */
    public function index(Request $request)
    {
        $search = $request->get('search', '');

        $users = User::query()
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"))
            ->orderByDesc('points_balance')
            ->paginate(20)
            ->through(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'coins_balance' => (int) ($user->points_balance ?? 0),
                'avatar' => $user->profile_photo_url ?? null,
            ]);

        return Inertia::render('Admin/Points/Index', [
            'users' => $users,
            'search' => $search,
        ]);
    }

    /**
     * Show the form to add or deduct points for a specific user.
     */
    public function create(User $user)
    {
        return Inertia::render('Admin/Points/Create', [
            'client' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'points_balance' => (int) ($user->points_balance ?? 0),
            ],
        ]);
    }

    /**
     * Add or deduct points from a specific user.
     */
    public function adjustPoints(Request $request, User $user)
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'not_in:0'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $amount = (int) $validated['amount'];
        $reason = trim($validated['reason']);
        $label = $amount > 0 ? __('general.admin_points_credit_log', ['reason' => $reason, 'time' => now()->format('Y-m-d H:i')]) : __('general.admin_points_deduction_log', ['reason' => $reason, 'time' => now()->format('Y-m-d H:i')]);

        $user->increment('points_balance', $amount);

        PointTransaction::create([
            'user_id' => $user->id,
            'type' => $amount > 0 ? 'earned' : 'used',
            'points' => $amount,
            'description' => $label,
        ]);

        $abs = abs($amount);

        if ($amount > 0) {
            return back()->with('success', __('general.points_added_success', ['amount' => $abs, 'name' => $user->name]));
        } else {
            return back()->with('success', __('general.points_deducted_success', ['amount' => $abs, 'name' => $user->name]));
        }
    }

    /**
     * Return the points history for a specific user (JSON, used by modal).
     */
    public function history(User $user)
    {
        $actions = PointTransaction::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(function ($t) {
                return [
                    'id' => $t->id,
                    'action_name' => $t->description ?: ucfirst($t->type),
                    'coins_reward' => $t->points,
                    'created_at' => $t->created_at,
                ];
            });

        return response()->json($actions);
    }
}
