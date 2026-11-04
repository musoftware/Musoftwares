<?php

declare(strict_types=1);

namespace App\Http\Controllers\Privacy;

use App\Enums\ConsentAction;
use App\Enums\ConsentType;
use App\Enums\DataSubjectRequestType;
use App\Http\Controllers\Controller;
use App\Services\Privacy\ConsentAuditService;
use App\Services\Privacy\DataSubjectRequestService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class DataSubjectRequestController extends Controller
{
    public function updateConsents(
        Request $request,
        ConsentAuditService $consentService
    ): RedirectResponse {
        $validated = $request->validate([
            'consent_type' => [
                'required',
                'string',
                'in:direct_marketing_email,direct_marketing_sms,cookies_tracking,cross_border_transfer',
            ],
            'granted' => ['required', 'boolean'],
        ]);

        $user = $request->user();
        $consentType = ConsentType::from($validated['consent_type']);
        $action = $validated['granted'] ? ConsentAction::Granted : ConsentAction::Withdrawn;

        $consentService->record($user, $consentType, $action, $request);

        if ($consentType === ConsentType::DirectMarketingEmail) {
            $user->forceFill([
                'winback_unsubscribed_at' => $validated['granted'] ? null : now(),
            ])->save();
        }

        if ($consentType === ConsentType::CookiesTracking) {
            Cookie::queue(Cookie::make(ConsentAuditService::TRACKING_COOKIE_NAME, $action->value, 60 * 24 * 365));
        }

        return back()->with('status', __('privacy_compliance.consent_updated_toast'));
    }

    public function exportData(
        Request $request,
        DataSubjectRequestService $dsrService
    ): Response {
        $request->validate([
            'format' => ['nullable', 'string', 'in:json,pdf'],
        ]);

        $user = $request->user();
        $dsrService->submitRequest($user, DataSubjectRequestType::AccessData, 'Self-service data portability export');
        $payload = $dsrService->buildUserDataExport($user);
        $format = strtolower((string) $request->query('format', 'json'));

        if ($format === 'pdf') {
            return Pdf::loadView('privacy.data-export-pdf', [
                'payload' => $payload,
                'user' => $user,
            ])->download("personal-data-user-{$user->id}.pdf");
        }

        $filename = "personal-data-user-{$user->id}.json";
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';

        return response($json, 200, [
            'Content-Type' => 'application/json; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function storeRequest(
        Request $request,
        DataSubjectRequestService $dsrService
    ): RedirectResponse {
        $validated = $request->validate([
            'request_type' => [
                'required',
                'string',
                'in:delete_account,restrict_processing,update_data',
            ],
            'details' => ['nullable', 'string', 'max:2000'],
        ]);

        $dsrService->submitRequest(
            $request->user(),
            DataSubjectRequestType::from($validated['request_type']),
            $validated['details'] ?? null
        );

        return back()->with('status', __('privacy_compliance.dsr_submitted_success'));
    }
}

