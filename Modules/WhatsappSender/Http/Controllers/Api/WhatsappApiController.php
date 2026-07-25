<?php

namespace Modules\WhatsappSender\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\WhatsappSender\Http\Requests\SendWhatsappMessageRequest;
use Modules\WhatsappSender\Models\WhatsappAccount;
use Modules\WhatsappSender\Models\WhatsappLog;
use Modules\WhatsappSender\Services\MetaWhatsappService;

class WhatsappApiController extends Controller
{
    public function __construct(
        protected MetaWhatsappService $whatsappService
    ) {}

    /**
     * Send WhatsApp message via Meta Cloud API.
     */
    public function send(SendWhatsappMessageRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        try {
            // Retrieve specified account or default active account for the user
            $accountQuery = WhatsappAccount::where('user_id', $user->id)
                ->where('status', 'active');

            if (! empty($validated['whatsapp_account_id'])) {
                $accountQuery->where('id', $validated['whatsapp_account_id']);
            }

            $account = $accountQuery->first();

            if (! $account) {
                return response()->json([
                    'success' => false,
                    'status' => 'error',
                    'message' => 'No active WhatsApp account found for this user. Please connect an account first.',
                    'timestamp' => now()->toIso8601String(),
                ], 422);
            }

            $templateData = null;
            if (($validated['message_type'] ?? 'text') === 'template') {
                $templateData = [
                    'name' => $validated['template_name'] ?? 'hello_world',
                    'language' => $validated['template_language'] ?? 'en_US',
                    'components' => $validated['template_components'] ?? null,
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
                    'success' => true,
                    'status' => 'sent',
                    'message' => 'WhatsApp message dispatched successfully via Meta Cloud API.',
                    'data' => [
                        'meta_message_id' => $result['meta_message_id'],
                        'log_id' => $result['log_id'],
                        'recipient_phone' => preg_replace('/[^0-9]/', '', $validated['recipient_phone']),
                        'sender_phone_number_id' => $account->phone_number_id,
                    ],
                    'timestamp' => now()->toIso8601String(),
                ], 200);
            }

            return response()->json([
                'success' => false,
                'status' => 'failed',
                'message' => $result['error'] ?? 'Meta API rejected the message request.',
                'data' => [
                    'log_id' => $result['log_id'] ?? null,
                ],
                'timestamp' => now()->toIso8601String(),
            ], 400);
        } catch (\Throwable $e) {
            Log::error('[WhatsappApiController] Unhandled exception during message dispatch: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'recipient' => $validated['recipient_phone'] ?? null,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'An internal server error occurred while dispatching the WhatsApp message.',
                'error_detail' => config('app.debug') ? $e->getMessage() : null,
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * List user's connected WhatsApp accounts.
     */
    public function accounts(Request $request): JsonResponse
    {
        $accounts = WhatsappAccount::where('user_id', $request->user()->id)
            ->select(['id', 'name', 'phone_number_id', 'waba_id', 'status', 'created_at'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $accounts,
            'count' => $accounts->count(),
            'timestamp' => now()->toIso8601String(),
        ], 200);
    }

    /**
     * Fetch user's message delivery logs.
     */
    public function logs(Request $request): JsonResponse
    {
        $limit = min((int) $request->input('limit', 20), 100);

        $logs = WhatsappLog::where('user_id', $request->user()->id)
            ->select(['id', 'whatsapp_account_id', 'recipient_phone', 'message_type', 'message_body', 'status', 'meta_message_id', 'error_message', 'created_at'])
            ->orderBy('created_at', 'desc')
            ->paginate($limit);

        return response()->json([
            'success' => true,
            'data' => $logs->items(),
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
                'last_page' => $logs->lastPage(),
            ],
            'timestamp' => now()->toIso8601String(),
        ], 200);
    }

    /**
     * Fetch details of a specific message log.
     */
    public function logDetails(Request $request, int $id): JsonResponse
    {
        $log = WhatsappLog::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->first();

        if (! $log) {
            return response()->json([
                'success' => false,
                'message' => 'Message log not found.',
                'timestamp' => now()->toIso8601String(),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $log,
            'timestamp' => now()->toIso8601String(),
        ], 200);
    }
}
