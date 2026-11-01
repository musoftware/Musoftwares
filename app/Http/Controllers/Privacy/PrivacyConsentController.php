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

class PrivacyConsentController extends Controller
{
    public function storeGateConsent(
        Request $request,
        ConsentAuditService $consentService
    ): JsonResponse|RedirectResponse {
        $request->validate([
            'essential_consent' => ['required', 'accepted'],
            'cookies_tracking' => ['required', 'boolean'],
        ]);

        $user = $request->user();
        $version = $consentService->currentPolicyVersion();
        $trackingGranted = $request->boolean('cookies_tracking');
        $trackingAction = $trackingGranted ? ConsentAction::Granted : ConsentAction::Withdrawn;

        $consentService->record($user, ConsentType::PrivacyPolicy, ConsentAction::Granted, $request, $version);
        $consentService->record($user, ConsentType::CrossBorderTransfer, ConsentAction::Granted, $request, $version);
        $consentService->record($user, ConsentType::CookiesTracking, $trackingAction, $request, $version);

        Cookie::queue(Cookie::make(ConsentAuditService::ESSENTIAL_COOKIE_NAME, $version, 60 * 24 * 365));
        Cookie::queue(Cookie::make(ConsentAuditService::TRACKING_COOKIE_NAME, $trackingAction->value, 60 * 24 * 365));
        Cookie::queue(Cookie::forget(ConsentAuditService::DECLINED_COOKIE_NAME));

        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return response()->json([
                'ok' => true,
                'policyVersion' => $version,
                'hasEssentialConsent' => true,
                'isBlocked' => false,
                'cookiesTracking' => $trackingAction->value,
            ]);
        }

        return redirect()->back(fallback: '/');
    }

    public function declineEssential(
        Request $request,
        ConsentAuditService $consentService
    ): JsonResponse|RedirectResponse {
        $user = $request->user();
        $version = $consentService->currentPolicyVersion();

        $consentService->record($user, ConsentType::PrivacyPolicy, ConsentAction::Withdrawn, $request, $version);
        $consentService->record($user, ConsentType::CookiesTracking, ConsentAction::Withdrawn, $request, $version);

        Cookie::queue(Cookie::forget(ConsentAuditService::ESSENTIAL_COOKIE_NAME));
        Cookie::queue(Cookie::make(ConsentAuditService::TRACKING_COOKIE_NAME, ConsentAction::Withdrawn->value, 60 * 24 * 365));
        Cookie::queue(Cookie::make(ConsentAuditService::DECLINED_COOKIE_NAME, '1', 60 * 24 * 30));

        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return response()->json([
                'ok' => true,
                'blocked' => true,
            ]);
        }

        return redirect('/');
    }
}

