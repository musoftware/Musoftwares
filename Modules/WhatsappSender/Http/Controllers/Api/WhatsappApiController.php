<?php

namespace Modules\WhatsappSender\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\WhatsappSender\Models\WhatsappAccount;
use Modules\WhatsappSender\Services\MetaWhatsappService;

class WhatsappApiController extends Controller
{
    public function __construct(
        protected MetaWhatsappService $whatsappService
    ) {}

    /**
     * Send WhatsApp message via programmatic API endpoint.
     */
    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'recipient_phone' => ['required', 'string', 'max:50'],
            'message_body' => ['required', 'string', 'max:4096'],
            'whatsapp_account_id' => ['nullable', 'exists:whatsapp_accounts,id'],
            'message_type' => ['nullable', 'string', 'in:text,template'],
            'template_name' => ['nullable', 'string'],
            'template_language' => ['nullable', 'string'],
        ]);

        $user = $request->user();

        // Get specified account or default active account for user
        $accountQuery = WhatsappAccount::where('user_id', $user->id)
            ->where('status', 'active');

        if (! empty($validated['whatsapp_account_id'])) {
            $accountQuery->where('id', $validated['whatsapp_account_id']);
        }

        $account = $accountQuery->first();

        if (! $account) {
            return response()->json([
                'status' => 'error',
                'message' => 'No active WhatsApp account found for this user. Please connect an account first.',
            ], 422);
        }

        $templateData = null;
        if (($validated['message_type'] ?? 'text') === 'template') {
            $templateData = [
                'name' => $validated['template_name'] ?? 'hello_world',
                'language' => $validated['template_language'] ?? 'en_US',
            ];
        }

        $result = $this->whatsappService->sendMessage(
            $account,
            $validated['recipient_phone'],
            $validated['message_body'],
            $validated['message_type'] ?? 'text',
            $templateData
        );

        if ($result['success']) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'meta_message_id' => $result['meta_message_id'],
                    'log_id' => $result['log_id'],
                ],
            ], 200);
        }

        return response()->json([
            'status' => 'error',
            'message' => $result['error'] ?? 'Failed to send WhatsApp message.',
        ], 400);
    }
}
