<?php

namespace Modules\Booking\app\Features\SmsNotifications\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Booking\app\Features\SmsNotifications\Models\SmsSetting;
use Modules\Booking\app\Features\SmsNotifications\Services\BookingSmsLimitsService;

class SmsSettingController extends Controller
{
    protected $limitsService;

    public function __construct(BookingSmsLimitsService $limitsService)
    {
        $this->limitsService = $limitsService;
        $this->middleware('auth:sanctum');
    }

    public function index()
    {
        $setting = SmsSetting::where('tenant_id', auth()->user()->tenant_id)->first();
        // Do not expose raw credentials, just indicate if it's set
        if ($setting) {
            $setting->has_credentials = !empty($setting->provider_credentials);
            unset($setting->provider_credentials);
        }
        return response()->json($setting);
    }

    public function store(Request $request)
    {
        if (!$this->limitsService->canSendSms(auth()->user()->tenant_id)) {
            return response()->json(['message' => 'Feature locked. Upgrade to use SMS Notifications.'], 403);
        }

        $validated = $request->validate([
            'provider_name' => 'required|string|in:twilio,smsmisr',
            'provider_credentials' => 'required|array', // Will be encrypted by cast
            'sender_id' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $setting = SmsSetting::updateOrCreate(
            ['tenant_id' => auth()->user()->tenant_id],
            $validated
        );

        return response()->json(['message' => 'Settings securely saved.'], 200);
    }
}
