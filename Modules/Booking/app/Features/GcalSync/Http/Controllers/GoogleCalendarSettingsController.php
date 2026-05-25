<?php

namespace Modules\Booking\app\Features\GcalSync\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Booking\app\Features\GcalSync\Models\GoogleCalendar;
use Modules\Booking\app\Features\GcalSync\Models\GoogleAccount;

class GoogleCalendarSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index()
    {
        $accounts = GoogleAccount::with('calendars')->where('tenant_id', auth()->user()->tenant_id)->get();
        return response()->json($accounts);
    }

    public function configureCalendar(Request $request, int $accountId)
    {
        $validated = $request->validate([
            'calendar_id' => 'required|string',
            'name' => 'required|string',
            'sync_direction' => 'required|in:two-way,push,pull',
            'is_active' => 'boolean',
        ]);

        $account = GoogleAccount::where('id', $accountId)
            ->where('tenant_id', auth()->user()->tenant_id)
            ->firstOrFail();

        $calendar = GoogleCalendar::updateOrCreate(
            ['tenant_id' => auth()->user()->tenant_id, 'account_id' => $account->id, 'calendar_id' => $validated['calendar_id']],
            $validated
        );

        return response()->json($calendar);
    }
}
