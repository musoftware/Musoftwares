<?php

namespace Modules\Booking\app\Features\Recurring\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Booking\app\Features\Recurring\Models\RecurringSeries;
use Modules\Booking\app\Features\Recurring\Jobs\GenerateFutureOccurrencesJob;
use Modules\Booking\app\Features\Recurring\Events\RecurringSeriesCreated;
use Modules\Booking\app\Features\Recurring\Services\BookingRecurringLimitsService;

class RecurringSeriesController extends Controller
{
    protected $limitsService;

    public function __construct(BookingRecurringLimitsService $limitsService)
    {
        $this->limitsService = $limitsService;
        }

    public function index()
    {
        $series = RecurringSeries::with('occurrences', 'exceptions')
            ->where('tenant_id', (app()->bound('currentTenant') ? app('currentTenant')->id : auth()->id()))
            ->get();
            
        return response()->json($series);
    }

    public function store(Request $request)
    {
        if (!$this->limitsService->canCreateSeries((app()->bound('currentTenant') ? app('currentTenant')->id : auth()->id()))) {
            return response()->json(['message' => 'Feature locked. Upgrade to use Recurring Appointments.'], 403);
        }

        $validated = $request->validate([
            'customer_id' => 'required|integer',
            'resource_id' => 'required|integer',
            'service_id' => 'required|integer',
            'rrule' => 'required|string',
            'starts_at' => 'required|date',
            'ends_at' => 'nullable|date',
            'duration_minutes' => 'integer|min:1'
        ]);

        $series = RecurringSeries::create($validated);

        // Instantly generate the first batch
        GenerateFutureOccurrencesJob::dispatch($series->id);
        
        event(new RecurringSeriesCreated($series));

        return response()->json($series, 201);
    }

    public function cancel(Request $request, int $id)
    {
        $series = RecurringSeries::where('id', $id)
            ->where('tenant_id', (app()->bound('currentTenant') ? app('currentTenant')->id : auth()->id()))
            ->firstOrFail();

        $series->update(['status' => 'cancelled']);
        
        // Also cancel future generated occurrences that haven't happened yet
        $series->occurrences()
            ->where('start_date', '>=', now()->format('Y-m-d'))
            ->update(['status' => 'cancelled']);

        return response()->json(['message' => 'Recurring series cancelled.']);
    }
}
