<?php

namespace Modules\Booking\app\Features\SmsNotifications\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Booking\app\Features\SmsNotifications\Models\SmsTemplate;
use Modules\Booking\app\Features\SmsNotifications\Services\BookingSmsLimitsService;

class SmsTemplateController extends Controller
{
    protected $limitsService;

    public function __construct(BookingSmsLimitsService $limitsService)
    {
        $this->limitsService = $limitsService;
        }

    public function index()
    {
        $templates = SmsTemplate::where('tenant_id', (app()->bound('currentTenant') ? app('currentTenant')->id : auth()->id()))->get();
        return response()->json($templates);
    }

    public function store(Request $request)
    {
        if (!$this->limitsService->canSendSms((app()->bound('currentTenant') ? app('currentTenant')->id : auth()->id()))) {
            return response()->json(['message' => 'Feature locked. Upgrade to use SMS Notifications.'], 403);
        }

        $validated = $request->validate([
            'type' => 'required|string|in:confirmation,reminder_24h,cancellation',
            'content' => 'required|string',
            'is_active' => 'boolean'
        ]);

        $template = SmsTemplate::updateOrCreate(
            ['tenant_id' => (app()->bound('currentTenant') ? app('currentTenant')->id : auth()->id()), 'type' => $validated['type']],
            $validated
        );

        return response()->json($template, 201);
    }
}
