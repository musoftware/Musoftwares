<?php

namespace Modules\Booking\app\Features\WaConfirm\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Booking\app\Features\WaConfirm\Services\ConfirmationTokenManager;
use Modules\Booking\app\Features\WaConfirm\Services\WhatsAppConfirmationActionProcessor;
use Exception;

class WaConfirmationPublicController extends Controller
{
    protected $tokenManager;
    protected $actionProcessor;

    public function __construct(
        ConfirmationTokenManager $tokenManager,
        WhatsAppConfirmationActionProcessor $actionProcessor
    ) {
        $this->tokenManager = $tokenManager;
        $this->actionProcessor = $actionProcessor;
    }

    /**
     * The public, unauthenticated endpoint where the customer lands after clicking
     * the WhatsApp confirmation link.
     */
    public function handleAction(string $rawToken)
    {
        try {
            // 1. Decrypt, validate, and mark the token as used (Throws Exception if invalid)
            $tokenModel = $this->tokenManager->validateAndConsumeToken($rawToken);

            // 2. Execute the requested action (confirm/cancel/reschedule)
            $this->actionProcessor->process($tokenModel);

            // In a real application, this might return an HTML view:
            // return view('booking::wa-confirm.success', ['action' => $tokenModel->action_type]);
            return response()->json([
                'success' => true,
                'message' => "Action '{$tokenModel->action_type}' was successfully applied to your booking.",
            ]);

        } catch (Exception $e) {
            // Return a safe error message without leaking DB data
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
