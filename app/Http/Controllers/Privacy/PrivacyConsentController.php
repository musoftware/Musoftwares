<?php

declare(strict_types=1);

namespace App\Http\Controllers\Privacy;

use App\Enums\ConsentAction;
use App\Enums\ConsentType;
use App\Http\Controllers\Controller;
use App\Services\Privacy\ConsentAuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

/**
 * Saves the cookie banner choice. The banner only asks about optional analytics and ad cookies,
 * so this logs that one choice and nothing else: reading the site needs no other agreement.
 */
class PrivacyConsentController extends Controller
{
    private const COOKIE_MINUTES = 60 * 24 * 365;

    public function storeCookieChoice(Request $request, ConsentAuditService $consentService): JsonResponse|RedirectResponse
    {
        $request->validate(['cookies_tracking' => ['required', 'boolean']]);

        $action = $request->boolean('cookies_tracking') ? ConsentAction::Granted : ConsentAction::Withdrawn;
        $consentService->record($request->user(), ConsentType::CookiesTracking, $action, $request);

        Cookie::queue(Cookie::make(ConsentAuditService::TRACKING_COOKIE_NAME, $action->value, self::COOKIE_MINUTES));

        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return response()->json(['ok' => true, 'cookiesTracking' => $action->value]);
        }

        return redirect()->back(fallback: '/');
    }
}
