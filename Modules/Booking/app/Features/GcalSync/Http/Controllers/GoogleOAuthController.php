<?php

namespace Modules\Booking\app\Features\GcalSync\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Booking\app\Features\GcalSync\Services\GoogleOAuthService;
use Modules\Booking\app\Features\GcalSync\Events\GoogleCalendarConnected;
use Modules\Booking\app\Features\GcalSync\Services\BookingGoogleLimitsService;

class GoogleOAuthController extends Controller
{
    protected $oauthService;
    protected $limitsService;

    public function __construct(GoogleOAuthService $oauthService, BookingGoogleLimitsService $limitsService)
    {
        $this->oauthService = $oauthService;
        $this->limitsService = $limitsService;
        }

    public function redirect(Request $request)
    {
        if (!$this->limitsService->canConnectCalendar((app()->bound('currentTenant') ? app('currentTenant')->id : auth()->id()))) {
            return response()->json(['message' => 'Feature locked. Upgrade to connect Google Calendar.'], 403);
        }

        return response()->json(['url' => $this->oauthService->getAuthUrl()]);
    }

    public function callback(Request $request)
    {
        try {
            $account = $this->oauthService->handleCallback($request);
            
            event(new GoogleCalendarConnected($account));
            
            return response()->json(['message' => 'Google Calendar connected successfully', 'account' => $account]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to connect Google account', 'error' => $e->getMessage()], 400);
        }
    }
}
