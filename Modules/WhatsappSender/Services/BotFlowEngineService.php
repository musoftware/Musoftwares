<?php

namespace Modules\WhatsappSender\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\WhatsappSender\Models\BotFlow;
use Modules\WhatsappSender\Models\BotFlowSession;
use Modules\WhatsappSender\Models\WhatsappBusiness;
use Modules\WhatsappSender\Models\WhatsappLog;
use Modules\WhatsappSender\Models\WhatsappTransaction;
use Modules\WhatsappSender\Models\TelegramBot;
use Modules\WhatsappSender\Models\TelegramSubscriber;
use Modules\WhatsappSender\Models\WhatsappContact;

class BotFlowEngineService
{
    public function __construct(
        protected MetaWhatsappService $whatsappService
    ) {}

    /**
     * Process an incoming message on WhatsApp or Telegram.
     */
    public function handleIncomingMessage(string $channel, int $businessId, string $senderId, string $messageText, ?int $botId = null, ?array $rawWebhookPayload = null): void
    {
        Log::info("[BotFlowEngine] Incoming message from {$senderId} on {$channel}: '{$messageText}'");

        $business = WhatsappBusiness::find($businessId);
        if (!$business || $business->status !== 'active') {
            return;
        }

        // Wallet Balance check: gate all bot flow responses if balance <= 0
        if ((float) $business->wallet_balance <= 0) {
            Log::warning("[BotFlowEngine] Business ID {$businessId} wallet balance is <= 0 (\${$business->wallet_balance}). Bot flows are gated.");
            return;
        }

        // 1. Check for an active session
        $session = BotFlowSession::where('channel', $channel)
            ->where('subscriber_identifier', $senderId)
            ->where(function($query) use ($businessId, $botId) {
                $query->where('whatsapp_business_id', $businessId)
                      ->orWhere('telegram_bot_id', $botId);
            })
            ->first();

        if ($session) {
            // Check session expiration (default 30 minutes)
            if ($session->expires_at && $session->expires_at->isPast()) {
                $session->delete();
                $session = null;
            }
        }

        if (!$session) {
            // 2. Start a new session if trigger matches
            $flow = $this->findMatchingFlow($channel, $businessId, $botId, $messageText);
            if (!$flow) {
                return; // No matching flow/trigger
            }

            // Find trigger node in flow
            $triggerNode = $this->findTriggerNode($flow);
            if (!$triggerNode) {
                return;
            }

            // Find target node from trigger node
            $nextNodeId = $this->getNextNodeIdFromEdge($flow, $triggerNode['id']);
            if (!$nextNodeId) {
                return;
            }

            $session = BotFlowSession::create([
                'channel' => $channel,
                'whatsapp_business_id' => $channel === 'whatsapp' ? $businessId : null,
                'telegram_bot_id' => $channel === 'telegram' ? $botId : null,
                'subscriber_identifier' => $senderId,
                'bot_flow_id' => $flow->id,
                'current_node_id' => $nextNodeId,
                'context_data' => [],
                'expires_at' => now()->addMinutes(30),
            ]);
        } else {
            // We have an active session! Let's check if the current node is a Message node waiting for input
            $flow = $session->flow;
            if (!$flow || !$flow->is_active) {
                $session->delete();
                return;
            }

            $currentNode = $this->findNodeInFlow($flow, $session->current_node_id);
            if ($currentNode && $currentNode['type'] === 'message') {
                // If the message node had buttons, check if user clicked one
                $buttons = $currentNode['data']['buttons'] ?? [];
                $matchedTargetNodeId = null;

                if (!empty($buttons)) {
                    foreach ($buttons as $btn) {
                        // Match either button label or button ID (if clicked in Telegram/WhatsApp postback)
                        if (strtolower(trim($messageText)) === strtolower(trim($btn['label'])) || $messageText === ($btn['value'] ?? null)) {
                            $matchedTargetNodeId = $btn['target_node_id'] ?? null;
                            break;
                        }
                    }
                }

                if ($matchedTargetNodeId) {
                    $session->update([
                        'current_node_id' => $matchedTargetNodeId,
                        'expires_at' => now()->addMinutes(30),
                    ]);
                } else {
                    // It didn't match any button, check if there is a general output edge from the node
                    $nextNodeId = $this->getNextNodeIdFromEdge($flow, $currentNode['id']);
                    if ($nextNodeId) {
                        $session->update([
                            'current_node_id' => $nextNodeId,
                            'expires_at' => now()->addMinutes(30),
                        ]);
                    } else {
                        // End flow if user sent random text and no buttons or outputs are matched
                        $session->delete();
                        return;
                    }
                }
            }
        }

        // 3. Run the flow execution loop
        $this->runExecutionLoop($session, $business);
    }

    /**
     * Handle Telegram button Callback Query specifically.
     */
    public function handleTelegramCallbackQuery(TelegramBot $bot, string $chatId, string $callbackData): void
    {
        Log::info("[BotFlowEngine] Telegram Callback Query from {$chatId}: '{$callbackData}'");

        $business = $bot->business;
        if (!$business || $business->status !== 'active') {
            return;
        }

        // Wallet Balance check
        if ((float) $business->wallet_balance <= 0) {
            return;
        }

        $session = BotFlowSession::where('channel', 'telegram')
            ->where('telegram_bot_id', $bot->id)
            ->where('subscriber_identifier', $chatId)
            ->first();

        if (!$session) {
            return; // No active session
        }

        // The callback data is the target node ID!
        $flow = $session->flow;
        if (!$flow || !$flow->is_active) {
            $session->delete();
            return;
        }

        // Verify the node exists in the flow
        $targetNode = $this->findNodeInFlow($flow, $callbackData);
        if ($targetNode) {
            $session->update([
                'current_node_id' => $callbackData,
                'expires_at' => now()->addMinutes(30),
            ]);

            // Run execution loop starting from the clicked button's target node
            $this->runExecutionLoop($session, $business);
        }
    }

    /**
     * Run the execution loop starting from the session's current node.
     */
    protected function runExecutionLoop(BotFlowSession $session, WhatsappBusiness $business): void
    {
        $flow = $session->flow;
        $maxIterations = 20; // Prevent infinite loops
        $iteration = 0;

        while ($session && $iteration < $maxIterations) {
            $iteration++;
            $currentNode = $this->findNodeInFlow($flow, $session->current_node_id);
            if (!$currentNode) {
                $session->delete();
                break;
            }

            Log::info("[BotFlowEngine] Executing Node ID: {$currentNode['id']}, Type: {$currentNode['type']}");

            // Charge Bot Reply Fee (reduced fee)
            $fee = (float) $business->bot_reply_fee;

            if ($currentNode['type'] === 'message') {
                // Wallet check before sending message
                if ((float) $business->wallet_balance < $fee) {
                    Log::warning("[BotFlowEngine] Gating flow: insufficient balance (\${$business->wallet_balance} available, \${$fee} fee required).");
                    $session->delete();
                    break;
                }

                $text = $currentNode['data']['message_text'] ?? '';
                $buttons = $currentNode['data']['buttons'] ?? [];

                // Send the message
                $sentResult = $this->sendMessageToSubscriber($session, $business, $text, $buttons);

                if ($sentResult['success']) {
                    // Deduct balance atomically
                    DB::transaction(function () use ($business, $fee, $session) {
                        $lockedBiz = WhatsappBusiness::where('id', $business->id)->lockForUpdate()->first();
                        $newBalance = max(0, (float) $lockedBiz->wallet_balance - $fee);
                        $lockedBiz->update(['wallet_balance' => $newBalance]);

                        WhatsappTransaction::create([
                            'whatsapp_business_id' => $lockedBiz->id,
                            'user_id' => $lockedBiz->user_id,
                            'type' => 'debit_message_fee',
                            'amount' => $fee,
                            'balance_after' => $newBalance,
                            'description' => "Automated Chat Flow reply fee via " . ucfirst($session->channel) . " for subscriber {$session->subscriber_identifier}",
                        ]);
                    });

                    // Log the message
                    WhatsappLog::create([
                        'user_id' => $business->user_id,
                        'telegram_bot_id' => $session->channel === 'telegram' ? $session->telegram_bot_id : null,
                        'whatsapp_business_id' => $business->id,
                        'recipient_phone' => $session->subscriber_identifier,
                        'channel' => $session->channel,
                        'cost_charged' => $fee,
                        'message_type' => empty($buttons) ? 'text' : 'button',
                        'message_body' => $text,
                        'status' => 'sent',
                        'meta_message_id' => $sentResult['message_id'] ?? null,
                    ]);

                    // If message has buttons, we pause the execution loop here and wait for input/callback
                    if (!empty($buttons)) {
                        break; // Stop loop, leave session open at this node
                    }

                    // Otherwise, find the next node via edge
                    $nextNodeId = $this->getNextNodeIdFromEdge($flow, $currentNode['id']);
                    if ($nextNodeId) {
                        $session->update(['current_node_id' => $nextNodeId]);
                    } else {
                        // End of flow
                        $session->delete();
                        $session = null;
                    }
                } else {
                    // Failed to send message
                    Log::error("[BotFlowEngine] Failed to send automated reply: " . $sentResult['error']);
                    $session->delete();
                    break;
                }
            }
            elseif ($currentNode['type'] === 'delay') {
                $seconds = (int) ($currentNode['data']['seconds'] ?? 1);
                $seconds = min(5, max(1, $seconds)); // Safe clamp: 1 to 5 seconds
                Log::info("[BotFlowEngine] Sleeping for {$seconds}s...");
                sleep($seconds);

                $nextNodeId = $this->getNextNodeIdFromEdge($flow, $currentNode['id']);
                if ($nextNodeId) {
                    $session->update(['current_node_id' => $nextNodeId]);
                } else {
                    $session->delete();
                    $session = null;
                }
            }
            elseif ($currentNode['type'] === 'condition') {
                $conditionMet = $this->evaluateCondition($session, $currentNode);
                // Look for true/false output edges
                $edgePort = $conditionMet ? 'true' : 'false';
                $nextNodeId = $this->getNextNodeIdFromEdge($flow, $currentNode['id'], $edgePort);

                if ($nextNodeId) {
                    $session->update(['current_node_id' => $nextNodeId]);
                } else {
                    $session->delete();
                    $session = null;
                }
            }
            elseif ($currentNode['type'] === 'action') {
                $this->executeAction($session, $currentNode);
                $nextNodeId = $this->getNextNodeIdFromEdge($flow, $currentNode['id']);

                if ($nextNodeId) {
                    $session->update(['current_node_id' => $nextNodeId]);
                } else {
                    $session->delete();
                    $session = null;
                }
            }
            else {
                // Unknown node type
                $session->delete();
                $session = null;
            }
        }
    }

    /**
     * Send WhatsApp/Telegram message with optional quick reply buttons.
     */
    protected function sendMessageToSubscriber(BotFlowSession $session, WhatsappBusiness $business, string $text, array $buttons): array
    {
        if ($session->channel === 'telegram') {
            $bot = TelegramBot::find($session->telegram_bot_id);
            if (!$bot) {
                return ['success' => false, 'error' => 'Telegram Bot not found.'];
            }

            $url = "https://api.telegram.org/bot{$bot->token}/sendMessage";
            $payload = [
                'chat_id' => $session->subscriber_identifier,
                'text' => $text,
                'parse_mode' => 'HTML',
            ];

            if (!empty($buttons)) {
                $keyboard = [];
                foreach ($buttons as $btn) {
                    $keyboard[] = [[
                        'text' => $btn['label'],
                        'callback_data' => $btn['target_node_id'] // callback data is the target node ID!
                    ]];
                }
                $payload['reply_markup'] = [
                    'inline_keyboard' => $keyboard
                ];
            }

            try {
                $response = Http::acceptJson()->post($url, $payload);
                if ($response->successful() && $response->json('ok')) {
                    return [
                        'success' => true,
                        'message_id' => $response->json('result.message_id')
                    ];
                }
                return ['success' => false, 'error' => $response->json('description') ?? 'Telegram API error.'];
            } catch (\Throwable $e) {
                return ['success' => false, 'error' => $e->getMessage()];
            }
        }
        else {
            // WhatsApp channel
            $account = $business->accounts()->where('status', 'active')->first();
            if (!$account) {
                return ['success' => false, 'error' => 'No active WhatsApp account connected.'];
            }

            if (empty($buttons)) {
                // Send standard text message
                return $this->whatsappService->sendMessage($account, $session->subscriber_identifier, $text);
            }

            // WhatsApp Quick Reply Buttons (limit: max 3 buttons)
            $formattedButtons = [];
            $limitedButtons = array_slice($buttons, 0, 3);
            foreach ($limitedButtons as $btn) {
                $formattedButtons[] = [
                    'type' => 'reply',
                    'reply' => [
                        'id' => $btn['target_node_id'], // Quick reply button payload ID is the target node ID
                        'title' => substr($btn['label'], 0, 20) // WABA button title limit: 20 chars
                    ]
                ];
            }

            $payload = [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $session->subscriber_identifier,
                'type' => 'interactive',
                'interactive' => [
                    'type' => 'button',
                    'body' => [
                        'text' => $text
                    ],
                    'action' => [
                        'buttons' => $formattedButtons
                    ]
                ]
            ];

            try {
                $url = "https://graph.facebook.com/v21.0/{$account->phone_number_id}/messages";
                $response = Http::withToken($account->access_token)->acceptJson()->post($url, $payload);
                if ($response->successful() && $response->json('messages')) {
                    return [
                        'success' => true,
                        'message_id' => $response->json('messages.0.id')
                    ];
                }
                return ['success' => false, 'error' => $response->json('error.message') ?? 'WhatsApp API error.'];
            } catch (\Throwable $e) {
                return ['success' => false, 'error' => $e->getMessage()];
            }
        }
    }

    /**
     * Evaluate visual flowchart condition card.
     */
    protected function evaluateCondition(BotFlowSession $session, array $node): bool
    {
        $type = $node['data']['condition_type'] ?? 'wallet_balance';
        $operator = $node['data']['operator'] ?? '=';
        $value = $node['data']['value'] ?? '';

        if ($type === 'wallet_balance') {
            $business = $session->flow->business;
            $balance = (float) $business->wallet_balance;
            $compareVal = (float) $value;

            return match ($operator) {
                '>' => $balance > $compareVal,
                '<' => $balance < $compareVal,
                '=' => $balance == $compareVal,
                default => false,
            };
        }

        // Custom field condition
        if ($session->channel === 'telegram') {
            $subscriber = TelegramSubscriber::where('telegram_bot_id', $session->telegram_bot_id)
                ->where('chat_id', $session->subscriber_identifier)
                ->first();
            $customFields = $subscriber ? ($subscriber->custom_fields ?? []) : [];
        } else {
            $subscriber = WhatsappContact::where('phone', $session->subscriber_identifier)
                ->whereHas('group', function($q) use ($session) {
                    $q->where('whatsapp_business_id', $session->whatsapp_business_id);
                })->first();
            $customFields = $subscriber ? ($subscriber->custom_fields ?? []) : [];
        }

        $fieldName = $node['data']['field_name'] ?? '';
        $fieldVal = $customFields[$fieldName] ?? '';

        return match ($operator) {
            '=' => strtolower($fieldVal) === strtolower($value),
            'contains' => str_contains(strtolower($fieldVal), strtolower($value)),
            default => false,
        };
    }

    /**
     * Execute visual flowchart action card.
     */
    protected function executeAction(BotFlowSession $session, array $node): void
    {
        $type = $node['data']['action_type'] ?? 'set_field';

        if ($type === 'set_field') {
            $fieldName = $node['data']['field_name'] ?? '';
            $fieldVal = $node['data']['field_value'] ?? '';
            if (empty($fieldName)) return;

            if ($session->channel === 'telegram') {
                $subscriber = TelegramSubscriber::where('telegram_bot_id', $session->telegram_bot_id)
                    ->where('chat_id', $session->subscriber_identifier)
                    ->first();
                if ($subscriber) {
                    $customFields = $subscriber->custom_fields ?? [];
                    $customFields[$fieldName] = $fieldVal;
                    $subscriber->update(['custom_fields' => $customFields]);
                }
            } else {
                $subscriber = WhatsappContact::where('phone', $session->subscriber_identifier)
                    ->whereHas('group', function($q) use ($session) {
                        $q->where('whatsapp_business_id', $session->whatsapp_business_id);
                    })->first();
                if ($subscriber) {
                    $customFields = $subscriber->custom_fields ?? [];
                    $customFields[$fieldName] = $fieldVal;
                    $subscriber->update(['custom_fields' => $customFields]);
                }
            }
        }
        elseif ($type === 'webhook') {
            $url = $node['data']['webhook_url'] ?? '';
            if (empty($url)) return;

            try {
                Http::timeout(5)->post($url, [
                    'channel' => $session->channel,
                    'subscriber_id' => $session->subscriber_identifier,
                    'bot_flow_id' => $session->bot_flow_id,
                    'current_node_id' => $node['id'],
                ]);
            } catch (\Throwable $e) {
                Log::error("[BotFlowEngine] Webhook action failed: " . $e->getMessage());
            }
        }
    }

    /**
     * Find a matching bot flow based on trigger type and keyword.
     */
    protected function findMatchingFlow(string $channel, int $businessId, ?int $botId, string $messageText): ?BotFlow
    {
        $flows = BotFlow::where('whatsapp_business_id', $businessId)
            ->where('channel', $channel)
            ->where('is_active', true)
            ->when($channel === 'telegram', function($q) use ($botId) {
                $q->where('telegram_bot_id', $botId);
            })
            ->get();

        $text = strtolower(trim($messageText));

        // 1. Keyword search (exact or matches)
        foreach ($flows as $flow) {
            if ($flow->trigger_type === 'keyword') {
                $keywords = $flow->trigger_keywords ?? [];
                foreach ($keywords as $kw) {
                    if ($text === strtolower(trim($kw))) {
                        return $flow;
                    }
                }
            }
        }

        // 2. Start Bot search (For Telegram /start command)
        if ($channel === 'telegram' && str_starts_with($text, '/start')) {
            foreach ($flows as $flow) {
                if ($flow->trigger_type === 'start_bot') {
                    return $flow;
                }
            }
        }

        // 3. Fallback to Default Auto-responder flow
        foreach ($flows as $flow) {
            if ($flow->trigger_type === 'default') {
                return $flow;
            }
        }

        return null;
    }

    /**
     * Find the trigger node in the flow JSON.
     */
    protected function findTriggerNode(BotFlow $flow): ?array
    {
        $nodes = $flow->nodes ?? [];
        foreach ($nodes as $node) {
            if ($node['type'] === 'trigger') {
                return $node;
            }
        }
        return null;
    }

    /**
     * Find any node inside the flow by its ID.
     */
    protected function findNodeInFlow(BotFlow $flow, string $nodeId): ?array
    {
        $nodes = $flow->nodes ?? [];
        foreach ($nodes as $node) {
            if ($node['id'] === $nodeId) {
                return $node;
            }
        }
        return null;
    }

    /**
     * Find the connected target node ID from a source node and optional source handle/port.
     */
    protected function getNextNodeIdFromEdge(BotFlow $flow, string $sourceNodeId, ?string $sourcePortHandle = null): ?string
    {
        $edges = $flow->edges ?? [];
        foreach ($edges as $edge) {
            if ($edge['source'] === $sourceNodeId) {
                // If a specific port handle is requested (e.g. true/false for condition)
                if ($sourcePortHandle !== null) {
                    if (($edge['sourceHandle'] ?? null) === $sourcePortHandle) {
                        return $edge['target'];
                    }
                } else {
                    return $edge['target'];
                }
            }
        }
        return null;
    }
}
