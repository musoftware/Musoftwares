<?php

namespace Modules\Booking\app\Features\Reminders\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Booking\app\Features\Reminders\Models\BookingWaReminder;
use Modules\Booking\app\Features\Reminders\Services\WaReminderLimitsService;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class WaReminderLogController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(function ($request, $next) {
                if (!feature('booking-wa-reminders')) {
                    return response()->json(['message' => 'Feature locked. Upgrade to enable WhatsApp reminders.'], 403);
                }
                return $next($request);
            }),
        ];
    }

    public function index(Request $request)
    {
        // View logs of sent/pending/failed reminders
        $logs = BookingWaReminder::with(['booking', 'template'])
            ->orderBy('scheduled_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json($logs);
    }

    public function getLimits(WaReminderLimitsService $limitsService)
    {
        $tenantId = (app()->bound('currentTenant') ? app('currentTenant')->id : auth()->id());
        
        return response()->json([
            'can_use' => $limitsService->canUse($tenantId),
            'remaining_usage' => $limitsService->getRemainingUsage($tenantId),
        ]);
    }
}
