<?php

namespace App\Http\Controllers;

use App\Enums\ConsentAction;
use App\Enums\ConsentType;
use App\Services\GuestTicketCreator;
use App\Services\Privacy\ConsentAuditService;
use Illuminate\Http\Request;

class GuestTicketSubmissionController extends Controller
{
    public function store(Request $request, GuestTicketCreator $creator, ConsentAuditService $consentService)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'mobile' => 'required|string|max:20',
            'body' => 'required|string',
            'subject' => 'nullable|string|max:255',
            'privacy_consent' => 'nullable|boolean',
            'marketing_consent' => 'nullable|boolean',
        ]);

        if ($request->boolean('privacy_consent')) {
            $consentService->record($request->user(), ConsentType::PrivacyPolicy, ConsentAction::Granted, $request);
            $consentService->record($request->user(), ConsentType::CrossBorderTransfer, ConsentAction::Granted, $request);
        }

        if ($request->boolean('marketing_consent')) {
            $consentService->record($request->user(), ConsentType::DirectMarketingEmail, ConsentAction::Granted, $request);
            $consentService->record($request->user(), ConsentType::DirectMarketingSms, ConsentAction::Granted, $request);
        }

        $creator->create($data);

        return back()->with('success', __('general.ticket_submitted_success'));
    }
}
