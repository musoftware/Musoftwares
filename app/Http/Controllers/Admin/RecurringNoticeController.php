<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RecurringNotice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RecurringNoticeController extends Controller
{
    public function index(Request $request)
    {
        if ($request->wantsJson() || $request->ajax()) {
            return $this->jsonIndex();
        }

        return redirect()->route('admin.projects.index');
    }

    private function jsonIndex(): \Illuminate\Http\JsonResponse
    {
        $notices = RecurringNotice::latest()->get()->map(function (RecurringNotice $notice) {
            return [
                'id' => $notice->id,
                'title' => $notice->title,
                'message' => $notice->message,
                'type' => $notice->type,
                'start_date' => $notice->start_date?->toDateString(),
                'recurring' => $notice->recurring,
                'recurring_times' => (int) $notice->recurring_times,
                'recurring_times_week' => $notice->recurring_times_week,
                'recurring_times_month' => $notice->recurring_times_month,
                'recurring_times_year' => $notice->recurring_times_year,
                'is_active' => (bool) $notice->is_active,
            ];
        });

        $stats = [
            'total_active' => RecurringNotice::where('is_active', true)->count(),
            'due_today' => RecurringNotice::dueToday()->count(),
        ];

        return response()->json(['notices' => $notices, 'stats' => $stats]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $notice = new RecurringNotice();
        $this->fillNotice($notice, $data);
        $notice->save();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['ok' => true, 'notice' => $notice->id]);
        }

        return redirect()->route('admin.projects.index')->with('success', __('general.recurring_notice_added_successfully'));
    }

    public function show(Request $request, $id)
    {
        $notice = RecurringNotice::findOrFail($id);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'notice' => [
                    'id' => $notice->id,
                    'title' => $notice->title,
                    'message' => $notice->message,
                    'type' => $notice->type,
                    'start_date' => $notice->start_date?->toDateString(),
                    'recurring' => $notice->recurring,
                    'recurring_times' => (int) $notice->recurring_times,
                    'recurring_times_week' => $notice->recurring_times_week,
                    'recurring_times_month' => $notice->recurring_times_month,
                    'recurring_times_year' => $notice->recurring_times_year,
                    'is_active' => (bool) $notice->is_active,
                ],
            ]);
        }

        return redirect()->route('admin.projects.index');
    }

    public function edit($id)
    {
        return redirect()->route('admin.projects.index');
    }

    public function update(Request $request, $id)
    {
        $notice = RecurringNotice::findOrFail($id);
        $data = $this->validateData($request);

        $this->fillNotice($notice, $data);
        $notice->save();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['ok' => true, 'notice' => $notice->id]);
        }

        return redirect()->route('admin.projects.index')->with('success', __('general.recurring_notice_updated_successfully'));
    }

    public function destroy(Request $request, $id)
    {
        $notice = RecurringNotice::findOrFail($id);
        $notice->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('admin.projects.index')->with('success', __('general.recurring_notice_deleted'));
    }

    public function toggle(Request $request, $id)
    {
        $notice = RecurringNotice::findOrFail($id);
        $notice->is_active = !$notice->is_active;
        $notice->save();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['ok' => true, 'is_active' => (bool) $notice->is_active]);
        }

        return redirect()->back()->with('success', __('general.status_updated_successfully'));
    }

    private function validateData(Request $request): array
    {
        $rules = [
            'title' => 'required|string|max:255',
            'message' => 'nullable|string',
            'type' => 'required|in:info,success,warning,danger',
            'start_date' => 'required|date',
            'recurring' => 'required|in:day,week,month,year',
            'recurring_times' => 'required|integer|min:1',
        ];

        if ($request->input('recurring') === 'week') {
            $rules['recurring_times_week'] = 'required|array|min:1';
        }
        if ($request->input('recurring') === 'month') {
            $rules['recurring_times_month'] = 'required|array|min:1';
        }
        if ($request->input('recurring') === 'year') {
            $rules['recurring_times_year'] = 'required|array|min:1';
        }

        return $request->validate($rules);
    }

    private function fillNotice(RecurringNotice $notice, array $data): void
    {
        $notice->title = $data['title'];
        $notice->message = $data['message'] ?? null;
        $notice->type = $data['type'];
        $notice->start_date = $data['start_date'];
        $notice->current_date = $data['start_date'];
        $notice->recurring = $data['recurring'];
        $notice->recurring_times = $data['recurring_times'];

        $notice->recurring_times_week = null;
        $notice->recurring_times_month = null;
        $notice->recurring_times_year = null;

        if ($data['recurring'] === 'week' && !empty($data['recurring_times_week'])) {
            $notice->recurring_times_week = implode(',', $data['recurring_times_week']);
        }
        if ($data['recurring'] === 'month' && !empty($data['recurring_times_month'])) {
            $notice->recurring_times_month = implode(',', $data['recurring_times_month']);
        }
        if ($data['recurring'] === 'year' && !empty($data['recurring_times_year'])) {
            $notice->recurring_times_year = implode(',', $data['recurring_times_year']);
        }
    }
}
