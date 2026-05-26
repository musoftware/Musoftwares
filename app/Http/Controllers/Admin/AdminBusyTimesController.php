<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RecurringBusyTime;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminBusyTimesController extends Controller
{
    public function index(Request $request)
    {
        $query = RecurringBusyTime::with('user');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('is_recurring')) {
            $query->where('is_recurring', filter_var($request->is_recurring, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('day_of_week')) {
            $query->where('day_of_week', $request->day_of_week);
        }

        $busyTimes = $query
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString()
            ->through(fn($bt) => [
                'id'            => $bt->id,
                'is_recurring'  => $bt->is_recurring,
                'day_of_week'   => $bt->day_of_week,
                'specific_date' => $bt->specific_date?->format('Y-m-d'),
                'is_full_day'   => $bt->is_full_day,
                'start_time'    => $bt->start_time,
                'end_time'      => $bt->end_time,
                'reason'        => $bt->reason,
                'is_active'     => $bt->is_active,
                'created_at'    => $bt->created_at->toDateTimeString(),
                'user'          => $bt->user ? [
                    'id'    => $bt->user->id,
                    'name'  => $bt->user->name,
                    'email' => $bt->user->email,
                ] : null,
            ]);

        $stats = [
            'total'     => RecurringBusyTime::count(),
            'active'    => RecurringBusyTime::where('is_active', true)->count(),
            'recurring' => RecurringBusyTime::where('is_recurring', true)->count(),
            'one_off'   => RecurringBusyTime::where('is_recurring', false)->count(),
        ];

        return Inertia::render('Admin/BusyTimes/Index', [
            'busyTimes' => $busyTimes,
            'filters'   => $request->only(['user_id', 'is_recurring', 'is_active', 'day_of_week']),
            'stats'     => $stats,
        ]);
    }

    public function toggleActive(RecurringBusyTime $busyTime)
    {
        $busyTime->update(['is_active' => ! $busyTime->is_active]);

        return redirect()->back()->with('success', 'Busy time status updated.');
    }

    public function destroy(RecurringBusyTime $busyTime)
    {
        $busyTime->delete();

        return redirect()->route('admin.busy-times.index')->with('success', 'Busy time deleted.');
    }
}
