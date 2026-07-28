<?php

namespace Modules\WhatsappSender\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\WhatsappSender\Http\Requests\SendWhatsappMessageRequest;
use Modules\WhatsappSender\Models\WhatsappAccount;
use Modules\WhatsappSender\Models\TelegramBot;
use Modules\WhatsappSender\Models\WhatsappLog;
use Modules\WhatsappSender\Models\WhatsappTemplate;
use Modules\WhatsappSender\Services\MetaWhatsappService;
use Modules\WhatsappSender\Services\TelegramBotService;

class WhatsappApiController extends Controller
{
    public function __construct(
        protected MetaWhatsappService $whatsappService,
        protected TelegramBotService $telegramService
    ) {}

    /**
     * Send WhatsApp or Telegram message via API.
     */
    public function send(SendWhatsappMessageRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();
        $channel = $validated['channel'] ?? 'whatsapp';

        try {
            if ($channel === 'telegram') {
                $botQuery = TelegramBot::whereHas('business', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })->where('status', 'active');

                if (!empty($validated['telegram_bot_id'])) {
                    $botQuery->where('id', $validated['telegram_bot_id']);
                }

                $bot = $botQuery->first();

                if (!$bot) {
                    return response()->json([
                        'success' => false,
                        'status' => 'error',
                        'message' => 'No active Telegram Bot found. Please register a Telegram Bot first.',
                        'timestamp' => now()->toIso8601String(),
                    ], 422);
                }

                // Check if sending to a contact group
                if (!empty($validated['whatsapp_contact_group_id'])) {
                    $group = \Modules\WhatsappSender\Models\WhatsappContactGroup::where('user_id', $user->id)
                        ->where('id', $validated['whatsapp_contact_group_id'])
                        ->first();

                    if (!$group) {
                        return response()->json([
                            'success' => false,
                            'status' => 'error',
                            'message' => 'The specified contact group does not exist.',
                            'timestamp' => now()->toIso8601String(),
                        ], 422);
                    }

                    \Modules\WhatsappSender\Jobs\SendGroupCampaignJob::dispatch(
                        null,
                        $group,
                        $validated['message_type'] ?? 'text',
                        $validated['message_body'] ?? null,
                        $validated['template_name'] ?? null,
                        $validated['template_language'] ?? 'en_US',
                        $validated['template_components'] ?? null,
                        $bot,
                        'telegram'
                    );

                    return response()->json([
                        'success' => true,
                        'status' => 'dispatched',
                        'message' => 'Telegram contact group campaign has been successfully queued for background processing.',
                        'timestamp' => now()->toIso8601String(),
                    ], 202);
                }

                // Send individual Telegram message immediately
                $bodyText = $validated['message_body'] ?? '';
                if (($validated['message_type'] ?? 'text') === 'template' && $bot->business) {
                    $template = WhatsappTemplate::where('whatsapp_business_id', $bot->whatsapp_business_id)
                        ->where('name', $validated['template_name'])
                        ->first();
                    if ($template) {
                        $bodyComponent = collect($template->components)->firstWhere('type', 'BODY');
                        $bodyText = $bodyComponent['text'] ?? '';
                        if (!empty($validated['template_components'])) {
                            $bodyParams = collect($validated['template_components'])->firstWhere('type', 'body')['parameters'] ?? [];
                            foreach ($bodyParams as $index => $param) {
                                $bodyText = str_replace('{{' . ($index + 1) . '}}', $param['text'] ?? '', $bodyText);
                            }
                        }
                    }
                }

                $result = $this->telegramService->sendMessage(
                    $bot,
                    $validated['recipient_phone'],
                    $bodyText,
                    $validated['message_type'] ?? 'text'
                );

                if ($result['success']) {
                    return response()->json([
                        'success' => true,
                        'status' => 'sent',
                        'message' => 'Telegram message dispatched successfully.',
                        'data' => [
                            'telegram_message_id' => $result['message_id'],
                            'log_id' => $result['log_id'],
                            'recipient_chat_id' => $validated['recipient_phone'],
                            'bot_username' => $bot->username,
                        ],
                        'timestamp' => now()->toIso8601String(),
                    ], 200);
                }

                return response()->json([
                    'success' => false,
                    'status' => 'failed',
                    'message' => $result['error'] ?? 'Telegram API rejected request.',
                    'data' => ['log_id' => $result['log_id'] ?? null],
                    'timestamp' => now()->toIso8601String(),
                ], 400);
            }

            // WhatsApp Channel
            $accountQuery = WhatsappAccount::where('user_id', $user->id)
                ->where('status', 'active');

            if (!empty($validated['whatsapp_account_id'])) {
                $accountQuery->where('id', $validated['whatsapp_account_id']);
            }

            $account = $accountQuery->first();

            if (!$account) {
                return response()->json([
                    'success' => false,
                    'status' => 'error',
                    'message' => 'No active WhatsApp account found. Please connect an account first.',
                    'timestamp' => now()->toIso8601String(),
                ], 422);
            }

            if (!empty($validated['whatsapp_contact_group_id'])) {
                $group = \Modules\WhatsappSender\Models\WhatsappContactGroup::where('user_id', $user->id)
                    ->where('id', $validated['whatsapp_contact_group_id'])
                    ->first();

                if (!$group) {
                    return response()->json([
                        'success' => false,
                        'status' => 'error',
                        'message' => 'The specified contact group does not exist.',
                        'timestamp' => now()->toIso8601String(),
                    ], 422);
                }

                \Modules\WhatsappSender\Jobs\SendGroupCampaignJob::dispatch(
                    $account,
                    $group,
                    $validated['message_type'] ?? 'text',
                    $validated['message_body'] ?? null,
                    $validated['template_name'] ?? null,
                    $validated['template_language'] ?? 'en_US',
                    $validated['template_components'] ?? null
                );

                return response()->json([
                    'success' => true,
                    'status' => 'dispatched',
                    'message' => 'WhatsApp contact group campaign has been successfully queued.',
                    'timestamp' => now()->toIso8601String(),
                ], 202);
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
                $validated['message_body'] ?? '',
                $validated['message_type'] ?? 'text',
                $templateData
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'status' => 'sent',
                    'message' => 'WhatsApp message dispatched successfully.',
                    'data' => [
                        'meta_message_id' => $result['meta_message_id'],
                        'log_id' => $result['log_id'],
                        'recipient_phone' => $validated['recipient_phone'],
                    ],
                    'timestamp' => now()->toIso8601String(),
                ], 200);
            }

            return response()->json([
                'success' => false,
                'status' => 'failed',
                'message' => $result['error'] ?? 'Meta API rejected message request.',
                'data' => ['log_id' => $result['log_id'] ?? null],
                'timestamp' => now()->toIso8601String(),
            ], 400);
        } catch (\Throwable $e) {
            Log::error('[WhatsappApiController] Exception during API send: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'An error occurred during dispatch: ' . $e->getMessage(),
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

    /**
     * Schedule a WhatsApp or Telegram campaign.
     */
    public function schedule(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'whatsapp_account_id' => ['nullable', 'integer', 'exists:whatsapp_accounts,id'],
            'telegram_bot_id' => ['nullable', 'integer', 'exists:telegram_bots,id'],
            'channel' => ['nullable', 'string', 'in:whatsapp,telegram'],
            'whatsapp_contact_group_id' => ['nullable', 'integer', 'exists:whatsapp_contact_groups,id'],
            'recipient_phone' => ['nullable', 'string'],
            'message_type' => ['required', 'string', 'in:text,template'],
            'message_body' => ['required_if:message_type,text', 'nullable', 'string', 'max:4096'],
            'template_name' => ['required_if:message_type,template', 'nullable', 'string', 'max:255'],
            'template_language' => ['nullable', 'string', 'max:10'],
            'template_components' => ['nullable', 'array'],
            'scheduled_at' => ['required', 'string'], // Expected format: YYYY-MM-DD HH:MM
        ]);

        $channel = $validated['channel'] ?? 'whatsapp';

        if (empty($validated['whatsapp_contact_group_id']) && empty($validated['recipient_phone'])) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'You must provide either whatsapp_contact_group_id or recipient_phone.',
                'timestamp' => now()->toIso8601String(),
            ], 422);
        }

        try {
            $cairoTime = \Carbon\Carbon::parse($validated['scheduled_at'], 'Africa/Cairo');
            $nowCairo = \Carbon\Carbon::now('Africa/Cairo');

            if ($cairoTime->lte($nowCairo)) {
                return response()->json([
                    'success' => false,
                    'status' => 'error',
                    'message' => 'The scheduled time must be in the future (Cairo Time: ' . $nowCairo->format('Y-m-d H:i') . ').',
                    'timestamp' => now()->toIso8601String(),
                ], 422);
            }

            $scheduledAtUtc = $cairoTime->utc();
        } catch (\Throwable) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Invalid scheduled_at date/time format. Please use YYYY-MM-DD HH:MM.',
                'timestamp' => now()->toIso8601String(),
            ], 422);
        }

        $businessId = null;
        $accountId = null;
        $botId = null;

        if ($channel === 'telegram') {
            $botQuery = TelegramBot::whereHas('business', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->where('status', 'active');

            if (!empty($validated['telegram_bot_id'])) {
                $botQuery->where('id', $validated['telegram_bot_id']);
            }

            $bot = $botQuery->first();

            if (!$bot) {
                return response()->json([
                    'success' => false,
                    'status' => 'error',
                    'message' => 'No active Telegram Bot found.',
                    'timestamp' => now()->toIso8601String(),
                ], 422);
            }

            $botId = $bot->id;
            $businessId = $bot->whatsapp_business_id;
        } else {
            $accountQuery = WhatsappAccount::where('user_id', $user->id)
                ->where('status', 'active');

            if (!empty($validated['whatsapp_account_id'])) {
                $accountQuery->where('id', $validated['whatsapp_account_id']);
            }

            $account = $accountQuery->first();

            if (!$account) {
                return response()->json([
                    'success' => false,
                    'status' => 'error',
                    'message' => 'No active WhatsApp account found.',
                    'timestamp' => now()->toIso8601String(),
                ], 422);
            }

            $accountId = $account->id;
            $businessId = $account->whatsapp_business_id;
        }

        $schedule = \Modules\WhatsappSender\Models\WhatsappSchedule::create([
            'user_id' => $user->id,
            'whatsapp_business_id' => $businessId,
            'whatsapp_account_id' => $accountId,
            'telegram_bot_id' => $botId,
            'whatsapp_contact_group_id' => $validated['whatsapp_contact_group_id'] ?? null,
            'recipient_phone' => $validated['recipient_phone'] ?? '',
            'channel' => $channel,
            'message_type' => $validated['message_type'],
            'message_body' => $validated['message_body'] ?? null,
            'template_name' => $validated['template_name'] ?? null,
            'template_language' => $validated['template_language'] ?? 'en_US',
            'template_components' => $validated['template_components'] ?? null,
            'scheduled_at' => $scheduledAtUtc,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'status' => 'scheduled',
            'message' => 'Campaign / Message has been scheduled successfully.',
            'data' => [
                'schedule_id' => $schedule->id,
                'scheduled_at_cairo' => $cairoTime->toIso8601String(),
                'scheduled_at_utc' => $scheduledAtUtc->toIso8601String(),
            ],
            'timestamp' => now()->toIso8601String(),
        ], 201);
    }

    /**
     * List WhatsApp templates available for the user.
     */
    public function templates(Request $request): JsonResponse
    {
        $user = $request->user();
        $businessIds = \Modules\WhatsappSender\Models\WhatsappBusiness::where('user_id', $user->id)
            ->pluck('id');

        $templates = \Modules\WhatsappSender\Models\WhatsappTemplate::whereIn('whatsapp_business_id', $businessIds)
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $templates,
            'count' => $templates->count(),
            'timestamp' => now()->toIso8601String(),
        ], 200);
    }

    /**
     * List contact groups available for the user.
     */
    public function groups(Request $request): JsonResponse
    {
        $groups = \Modules\WhatsappSender\Models\WhatsappContactGroup::where('user_id', $request->user()->id)
            ->withCount('contacts')
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $groups,
            'count' => $groups->count(),
            'timestamp' => now()->toIso8601String(),
        ], 200);
    }
}
