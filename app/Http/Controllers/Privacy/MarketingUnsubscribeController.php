<?php

declare(strict_types=1);

namespace App\Http\Controllers\Privacy;

use App\Enums\ConsentAction;
use App\Enums\ConsentType;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Privacy\ConsentAuditService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MarketingUnsubscribeController extends Controller
{
    public function __invoke(Request $request, User $user, ConsentAuditService $consentService): Response
    {
        if (! $request->hasValidSignature()) {
            abort(403, __('privacy_compliance.unsubscribe_invalid_link'));
        }

        $consentService->record($user, ConsentType::DirectMarketingEmail, ConsentAction::Withdrawn, $request);
        $consentService->record($user, ConsentType::DirectMarketingSms, ConsentAction::Withdrawn, $request);

        $user->forceFill([
            'winback_unsubscribed_at' => now(),
        ])->save();

        if ($request->isMethod('POST')) {
            return response('Unsubscribed', 200);
        }

        return response()->view('public.legal.unsubscribed', [
            'email' => $user->email,
        ]);
    }
}

