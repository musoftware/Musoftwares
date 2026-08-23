<?php

namespace Modules\WhatsappSender\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\WhatsappSender\Models\WhatsappAccount;
use Modules\WhatsappSender\Models\WhatsappBusiness;
use Modules\WhatsappSender\Models\WhatsappLog;
use Modules\WhatsappSender\Models\WhatsappTransaction;
use Modules\WhatsappSender\Models\WhatsappTemplate;
use Modules\WhatsappSender\Models\WhatsappContactGroup;
use Modules\WhatsappSender\Models\WhatsappContact;
use Modules\WhatsappSender\Models\WhatsappSchedule;
use Modules\WhatsappSender\Models\TelegramBot;
use Modules\WhatsappSender\Jobs\SendGroupCampaignJob;
use Modules\WhatsappSender\Services\MetaWhatsappService;

class WhatsappSenderController extends Controller
{
    public function __construct(
        protected MetaWhatsappService $whatsappService
    ) {}

    /**
     * Display the WhatsApp Sender dashboard.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $scope = $request->input('scope', 'my');

        $query = WhatsappBusiness::withCount('accounts')
            ->orderBy('created_at', 'asc');

        if (!$user->isAdmin() || $scope === 'my') {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                  ->orWhere('client_name', 'like', $search)
                  ->orWhere('client_email', 'like', $search)
                  ->orWhere('client_mobile', 'like', $search)
                  ->orWhere('client_whatsapp', 'like', $search);
            });
        }

        $userHasBusiness = WhatsappBusiness::where('user_id', $user->id)->exists();
        if (!$userHasBusiness) {
            $defaultBiz = WhatsappBusiness::create([
                'user_id' => $user->id,
                'name' => 'Default Business Client',
                'client_name' => $user->name,
                'client_email' => $user->email,
                'wallet_balance' => 10.0000, // $10 free credit balance
                'currency' => 'USD',
                'per_message_fee' => 0.0010,
                'bot_reply_fee' => 0.0005,
                'status' => 'active',
                'webhook_verify_token' => 'biz_wt_' . \Illuminate\Support\Str::random(24),
            ]);

            WhatsappTransaction::create([
                'whatsapp_business_id' => $defaultBiz->id,
                'user_id' => $user->id,
                'type' => 'credit_recharge',
                'amount' => 10.0000,
                'balance_after' => 10.0000,
                'description' => 'Welcome initial balance credit ($10.00 USD)',
            ]);
        }

        $businesses = $query->get();

        // Get or create API token for the user
        $apiToken = $user->tokens()->where('name', 'whatsapp-sender-api')->first()?->token
            ?? $user->createToken('whatsapp-sender-api')->plainTextToken;

        return Inertia::render('WhatsappSender/Index', [
            'businesses' => $businesses,
            'apiToken' => $apiToken,
            'filters' => [
                'search' => $request->input('search', ''),
                'scope' => $scope,
            ],
            'isAdmin' => $user->isAdmin(),
        ]);
    }

    /**
     * Show the dedicated business client workspace.
     */
    public function showBusinessWorkspace(Request $request, int $id): Response
    {
        $user = $request->user();
        $businessQuery = WhatsappBusiness::where('id', $id)->withCount('accounts');
        if (!$user->isAdmin()) {
            $businessQuery->where('user_id', $user->id);
        }
        $business = $businessQuery->firstOrFail();

        $accounts = WhatsappAccount::with('business')->where('whatsapp_business_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        $bots = TelegramBot::where('whatsapp_business_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        $templates = WhatsappTemplate::where('whatsapp_business_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        $contactGroups = WhatsappContactGroup::where('whatsapp_business_id', $id)
            ->withCount('contacts')
            ->orderBy('created_at', 'desc')
            ->get();

        $schedules = WhatsappSchedule::where('whatsapp_business_id', $id)
            ->with(['business:id,name', 'account:id,name', 'group:id,name', 'telegramBot:id,name'])
            ->orderBy('scheduled_at', 'desc')
            ->get();

        $logs = WhatsappLog::where('whatsapp_business_id', $id)
            ->with(['account:id,name,phone_number_id', 'telegramBot:id,name', 'business:id,name'])
            ->orderBy('created_at', 'desc')
            ->take(100)
            ->get();

        $transactions = WhatsappTransaction::where('whatsapp_business_id', $id)
            ->orderBy('created_at', 'desc')
            ->take(100)
            ->get();

        // Get or create API token for the user
        $apiToken = $user->tokens()->where('name', 'whatsapp-sender-api')->first()?->token
            ?? $user->createToken('whatsapp-sender-api')->plainTextToken;

        $webhookUrl = url('/api/v1/whatsapp/webhook');
        $webhookVerifyToken = \App\Models\AdminSettings::GetValue('whatsapp_webhook_verify_token', 'musoftware_whatsapp_verify_token_2026');

        $fbOauthToken = session('fb_oauth_token');
        if ($fbOauthToken) {
            session()->forget('fb_oauth_token');
        }

        $botIds = $bots->pluck('id');
        $telegramSubscriberGroups = \Modules\WhatsappSender\Models\TelegramSubscriberGroup::whereIn('telegram_bot_id', $botIds)
            ->withCount('subscribers')
            ->orderBy('created_at', 'desc')
            ->get();

        $telegramSubscribers = \Modules\WhatsappSender\Models\TelegramSubscriber::whereIn('telegram_bot_id', $botIds)
            ->with(['group:id,name'])
            ->orderBy('created_at', 'desc')
            ->get();

        $flows = \Modules\WhatsappSender\Models\BotFlow::where('whatsapp_business_id', $id)
            ->with(['telegramBot:id,name'])
            ->orderBy('created_at', 'desc')
            ->get();

        $hasFacebookApp = (!empty($business->facebook_client_id) && !empty($business->facebook_client_secret))
            || (!empty(config('services.facebook.client_id')) && !empty(config('services.facebook.client_secret')));

        return Inertia::render('WhatsappSender/Workspace', [
            'business' => $business,
            'accounts' => $accounts,
            'bots' => $bots,
            'templates' => $templates,
            'contactGroups' => $contactGroups,
            'schedules' => $schedules,
            'logs' => $logs,
            'transactions' => $transactions,
            'apiToken' => $apiToken,
            'webhookUrl' => $webhookUrl,
            'webhookVerifyToken' => $webhookVerifyToken,
            'facebookLoginUrl' => route('whatsapp.auth.facebook', ['business_id' => $business->id]),
            'fbOauthToken' => $fbOauthToken,
            'telegramSubscribers' => $telegramSubscribers,
            'telegramSubscriberGroups' => $telegramSubscriberGroups,
            'flows' => $flows,
            'isAdmin' => $user->isAdmin(),
            'hasFacebookApp' => $hasFacebookApp,
        ]);
    }

    /**
     * Display the Dedicated Full-Screen WhatsApp Web Live Chat Interface.
     */
    public function showDedicatedLiveChat(Request $request, int $id): Response
    {
        $user = $request->user();
        $businessQuery = WhatsappBusiness::where('id', $id);
        if (!$user->isAdmin()) {
            $businessQuery->where('user_id', $user->id);
        }
        $business = $businessQuery->firstOrFail();

        $accounts = WhatsappAccount::with('business')->where('whatsapp_business_id', $business->id)->get();
        $templates = WhatsappTemplate::where('whatsapp_business_id', $business->id)
            ->where('status', 'APPROVED')
            ->get();

        return Inertia::render('WhatsappSender/DedicatedLiveChat', [
            'business' => $business,
            'accounts' => $accounts,
            'templates' => $templates,
        ]);
    }

    /**
     * Update Meta Webhook Verify Token in AdminSettings.
     */
    public function updateWebhookSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'webhook_verify_token' => ['required', 'string', 'max:255'],
        ]);

        \App\Models\AdminSettings::SetValue('whatsapp_webhook_verify_token', trim($validated['webhook_verify_token']));

        return redirect()->back()->with('success', __('general.webhook_settings_saved_successfully') ?? 'Webhook verify token updated successfully!');
    }

    /**
     * Store or update Meta API credentials manually.
     */
    public function storeAccount(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'whatsapp_business_id' => ['nullable', 'exists:whatsapp_businesses,id'],
            'name' => ['required', 'string', 'max:255'],
            'phone_number_id' => ['required', 'string', 'max:255'],
            'waba_id' => ['nullable', 'string', 'max:255'],
            'access_token' => ['required', 'string'],
        ]);

        $businessId = $validated['whatsapp_business_id']
            ?? WhatsappBusiness::where('user_id', $request->user()->id)->first()?->id;

        // Verify credentials before saving
        $verification = $this->whatsappService->verifyAccountCredentials(
            $validated['phone_number_id'],
            $validated['access_token']
        );

        $metadata = [];
        $status = 'unregistered';
        if ($verification['valid']) {
            $metadata = $verification['data'];
            $metaStatus = strtoupper($metadata['phone_number_api_response']['body']['status'] ?? 'CONNECTED');
            $metadata['meta_connection_status'] = $metaStatus;
            $metadata['is_registered'] = false;

            if (!in_array($metaStatus, ['CONNECTED', 'APPROVED', 'ACTIVE'])) {
                $status = 'disconnected';
            } else {
                $status = 'unregistered';
            }
        } else {
            $status = 'disconnected';
        }

        WhatsappAccount::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'phone_number_id' => $validated['phone_number_id'],
            ],
            [
                'whatsapp_business_id' => $businessId,
                'name' => $validated['name'],
                'waba_id' => $validated['waba_id'] ?? null,
                'access_token' => $validated['access_token'],
                'status' => $status,
                'metadata' => $metadata,
            ]
        );

        return redirect()->back()->with('success', __('whatsapp-sender::messages.account_saved_successfully') ?? 'Account credentials saved successfully.');
    }

    /**
     * Update an existing WhatsApp account details.
     */
    public function updateAccount(Request $request, int $id): RedirectResponse
    {
        $account = WhatsappAccount::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone_number_id' => ['required', 'string', 'max:255'],
            'waba_id' => ['nullable', 'string', 'max:255'],
            'access_token' => ['nullable', 'string'],
        ]);

        $accessToken = !empty($validated['access_token']) ? trim($validated['access_token']) : $account->access_token;
        $phoneNumberId = trim($validated['phone_number_id']);

        $metadata = $account->metadata ?? [];
        $status = $account->status;

        if (!empty($validated['access_token']) || $phoneNumberId !== $account->phone_number_id) {
            $verification = $this->whatsappService->verifyAccountCredentials(
                $phoneNumberId,
                $accessToken
            );

            if ($verification['valid']) {
                $metadata = array_merge($metadata, $verification['data']);
                $status = (isset($metadata['status']) && $metadata['status'] !== 'CONNECTED') ? 'unregistered' : 'active';
            }
        }

        $account->update([
            'name' => trim($validated['name']),
            'phone_number_id' => $phoneNumberId,
            'waba_id' => !empty($validated['waba_id']) ? trim($validated['waba_id']) : null,
            'access_token' => $accessToken,
            'status' => $status,
            'metadata' => $metadata,
        ]);

        return redirect()->back()->with('success', 'WhatsApp account updated successfully.');
    }

    /**
     * Send a WhatsApp message via the web form.
     */
    public function sendMessage(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'whatsapp_account_id' => ['required', 'exists:whatsapp_accounts,id'],
            'recipient_phone' => ['required', 'string', 'max:50'],
            'message_body' => ['nullable', 'required_unless:message_type,template', 'string', 'max:4096'],
            'message_type' => ['nullable', 'string', 'in:text,template'],
            'template_name' => ['nullable', 'string'],
            'template_language' => ['nullable', 'string'],
            'template_components' => ['nullable', 'array'],
            'template_parameters' => ['nullable'],
        ]);

        $account = WhatsappAccount::where('user_id', $request->user()->id)
            ->where('id', $validated['whatsapp_account_id'])
            ->firstOrFail();

        $templateData = null;
        if (($validated['message_type'] ?? 'text') === 'template') {
            $templateData = [
                'name' => $validated['template_name'] ?? 'hello_world',
                'language' => $validated['template_language'] ?? 'en_US',
            ];

            if (!empty($validated['template_components'])) {
                $templateData['components'] = $validated['template_components'];
            } elseif (!empty($validated['template_parameters'])) {
                $params = is_string($validated['template_parameters'])
                    ? (json_decode($validated['template_parameters'], true) ?? explode(',', $validated['template_parameters']))
                    : (array) $validated['template_parameters'];

                $templateData['components'] = [
                    [
                        'type' => 'body',
                        'parameters' => array_map(fn($v) => ['type' => 'text', 'text' => trim((string) $v)], array_values($params)),
                    ],
                ];
            }
        }

        $messageBody = $validated['message_body'] ?? '';
        if (($validated['message_type'] ?? 'text') === 'template' && empty($messageBody)) {
            $messageBody = $validated['template_name'] ?? 'Template Message';
        }

        $result = $this->whatsappService->sendMessage(
            $account,
            $validated['recipient_phone'],
            $messageBody,
            $validated['message_type'] ?? 'text',
            $templateData
        );

        if ($result['success']) {
            return redirect()->back()
                ->with('success', "Message sent successfully! (Charged \${$result['cost_charged']} USD platform fee)")
                ->with('meta_response', $result['response'] ?? [
                    'messaging_product' => 'whatsapp',
                    'contacts' => [
                        ['input' => $validated['recipient_phone'], 'wa_id' => preg_replace('/[^0-9]/', '', $validated['recipient_phone'])],
                    ],
                    'messages' => [
                        ['id' => $result['meta_message_id'] ?? 'wamid.sandbox.demo'],
                    ],
                ]);
        }

        return redirect()->back()
            ->with('error', $result['error'] ?? __('whatsapp-sender::messages.message_failed'))
            ->with('meta_response', $result['response'] ?? [
                'error' => [
                    'message' => $result['error'] ?? 'Message delivery failed',
                    'type' => 'MetaGraphApiException',
                    'code' => 400,
                ],
            ]);
    }

    /**
     * Delete/disconnect a WhatsApp account.
     */
    public function destroyAccount(Request $request, int $id): RedirectResponse
    {
        $account = WhatsappAccount::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        $account->delete();

        return redirect()->back()->with('success', __('whatsapp-sender::messages.account_deleted_successfully') ?? 'Account disconnected successfully.');
    }

    /**
     * Update/Set WABA ID for a WhatsApp account.
     */
    public function updateWabaId(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'waba_id' => ['required', 'string', 'max:255'],
        ]);

        $account = WhatsappAccount::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        $account->update([
            'waba_id' => trim($validated['waba_id']),
        ]);

        return redirect()->back()->with('success', 'WABA ID updated successfully.');
    }

    /**
     * Register/activate a phone number via Meta API with a 6-digit PIN.
     */
    public function registerAccount(Request $request, int $id): RedirectResponse
    {
        $account = WhatsappAccount::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        if ($request->isMethod('GET')) {
            if ($account->whatsapp_business_id) {
                return redirect()->route('whatsapp.businesses.workspace', $account->whatsapp_business_id);
            }
            return redirect()->route('whatsapp.index');
        }

        $validated = $request->validate([
            'pin' => ['required', 'string', 'size:6', 'regex:/^[0-9]+$/'],
        ]);

        $result = $this->whatsappService->registerPhoneNumber($account, $validated['pin']);

        if ($result['success']) {
            return redirect()->back()
                ->with('success', $result['message'] ?? 'Phone number registered and activated on Meta Cloud API successfully!')
                ->with('meta_response', $result['response'] ?? null);
        }

        return redirect()->back()
            ->with('error', $result['error'] ?? 'Failed to register phone number.')
            ->with('meta_response', $result['response'] ?? null);
    }

    /**
     * Sync WhatsApp account status and metadata from Meta Graph API.
     */
    public function syncAccountStatus(Request $request, int $id): RedirectResponse
    {
        $account = WhatsappAccount::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        if ($request->isMethod('GET')) {
            if ($account->whatsapp_business_id) {
                return redirect()->route('whatsapp.businesses.workspace', $account->whatsapp_business_id);
            }
            return redirect()->route('whatsapp.index');
        }

        $verification = $this->whatsappService->verifyAccountCredentials(
            $account->phone_number_id,
            $account->access_token
        );

        if ($verification['valid']) {
            $metadata = $verification['data'];
            $status = 'active';
            if (isset($metadata['status']) && $metadata['status'] !== 'CONNECTED') {
                $status = 'unregistered';
            }

            $account->update([
                'status' => $status,
                'metadata' => $metadata,
            ]);

            $statusText = isset($metadata['status']) ? $metadata['status'] : 'active';
            return redirect()->back()->with('success', "Account status synced successfully! Current Meta status: {$statusText}.");
        }

        return redirect()->back()->with('error', $verification['error'] ?? 'Failed to sync account status from Meta.');
    }

    /**
     * Test WhatsApp account Meta Graph API connection and return raw JSON metadata.
     */
    public function testAccount(Request $request, int $id): RedirectResponse
    {
        $account = WhatsappAccount::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        if ($request->isMethod('GET')) {
            if ($account->whatsapp_business_id) {
                return redirect()->route('whatsapp.businesses.workspace', $account->whatsapp_business_id);
            }
            return redirect()->route('whatsapp.index');
        }

        $result = $this->whatsappService->testAccountConnection($account);

        if (!empty($result['is_connected']) || !empty($result['success'])) {
            return redirect()->back()
                ->with('success', $result['message'])
                ->with('meta_response', $result['data']);
        }

        return redirect()->back()
            ->with('error', $result['message'] ?? ($result['error'] ?? 'Meta API Connection Test Failed.'))
            ->with('meta_response', $result['data'] ?? null);
    }

    /**
     * Request a 6-digit verification code via SMS or VOICE from Meta.
     */
    public function requestCodeAccount(Request $request, int $id): RedirectResponse
    {
        $account = WhatsappAccount::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        if ($request->isMethod('GET')) {
            if ($account->whatsapp_business_id) {
                return redirect()->route('whatsapp.businesses.workspace', $account->whatsapp_business_id);
            }
            return redirect()->route('whatsapp.index');
        }

        $validated = $request->validate([
            'code_method' => ['required', 'string', 'in:SMS,VOICE,sms,voice'],
            'language' => ['nullable', 'string', 'max:10'],
        ]);

        $result = $this->whatsappService->requestVerificationCode(
            $account,
            strtoupper($validated['code_method']),
            $validated['language'] ?? 'ar'
        );

        if ($result['success']) {
            return redirect()->back()
                ->with('success', $result['message'])
                ->with('meta_response', $result['response'] ?? null);
        }

        return redirect()->back()
            ->with('error', $result['error'] ?? 'Failed to request verification code.')
            ->with('meta_response', $result['response'] ?? null);
    }

    /**
     * Send template messages immediately to a contact group.
     */
    public function sendGroupCampaign(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'whatsapp_account_id' => ['required', 'exists:whatsapp_accounts,id'],
            'whatsapp_contact_group_id' => ['required', 'exists:whatsapp_contact_groups,id'],
            'message_type' => ['required', 'string', 'in:text,template'],
            'message_body' => ['nullable', 'required_unless:message_type,template', 'string', 'max:4096'],
            'template_name' => ['required_if:message_type,template', 'nullable', 'string', 'max:255'],
            'template_language' => ['nullable', 'string', 'max:10'],
            'template_components' => ['nullable', 'array'],
        ]);

        $user = $request->user();

        $account = WhatsappAccount::where('user_id', $user->id)
            ->where('id', $validated['whatsapp_account_id'])
            ->firstOrFail();

        $group = WhatsappContactGroup::where('user_id', $user->id)
            ->where('id', $validated['whatsapp_contact_group_id'])
            ->firstOrFail();

        SendGroupCampaignJob::dispatch(
            $account,
            $group,
            $validated['message_type'],
            $validated['message_body'] ?? null,
            $validated['template_name'] ?? null,
            $validated['template_language'] ?? 'en_US',
            $validated['template_components'] ?? null
        );

        return redirect()->back()->with('success', 'Bulk campaign sending job has been dispatched to the queue.');
    }

    /**
     * Show Meta Developer App Setup Guide.
     */
    public function showMetaAppGuide(Request $request)
    {
        return \Inertia\Inertia::render('WhatsappSender/MetaAppGuide');
    }

    /**
     * Get active conversations for the business CRM inbox.
     */
    public function getConversations(Request $request, int $id)
    {
        $user = $request->user();
        $businessQuery = WhatsappBusiness::where('id', $id);
        if (!$user->isAdmin()) {
            $businessQuery->where('user_id', $user->id);
        }
        $business = $businessQuery->firstOrFail();

        $latestLogs = WhatsappLog::whereIn('id', function ($query) use ($id) {
                $query->selectRaw('MAX(id)')
                    ->from('whatsapp_logs')
                    ->where('whatsapp_business_id', $id)
                    ->groupBy('recipient_phone');
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $contacts = WhatsappContact::whereHas('group', function ($q) use ($id) {
                $q->where('whatsapp_business_id', $id);
            })
            ->get();

        $contactMap = [];
        foreach ($contacts as $c) {
            $rawP = $c->phone;
            $cleanP = preg_replace('/[^0-9]/', '', $rawP);
            $contactMap[$rawP] = $c;
            $contactMap[$cleanP] = $c;
            $contactMap['+' . $cleanP] = $c;
        }

        $conversations = $latestLogs->map(function ($log) use ($contactMap) {
            $phone = $log->recipient_phone;
            $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
            $contact = $contactMap[$phone] ?? $contactMap[$cleanPhone] ?? $contactMap['+' . $cleanPhone] ?? null;
            $hasReferral = !empty($log->payload['referral']);

            // Calculate free window: 72 hours for CTWA ads, 24 hours for organic customer inbound
            $hoursWindow = $hasReferral ? 72 : 24;
            $freeWindowExpiresAt = $log->created_at->addHours($hoursWindow)->toIso8601String();

            $tags = $contact?->custom_fields['tags'] ?? [];

            return [
                'recipient_phone' => $phone,
                'contact_name' => $contact?->name ?: "Customer {$phone}",
                'group_name' => $contact?->group?->name ?? null,
                'last_message' => $log->message_body,
                'last_message_type' => $log->message_type,
                'last_message_status' => $log->status,
                'last_message_direction' => $log->direction ?? ($log->status === 'inbound' ? 'inbound' : 'outbound'),
                'last_message_at' => $log->created_at->toIso8601String(),
                'channel' => $log->channel ?? 'whatsapp',
                'is_ctwa_ad' => $hasReferral,
                'referral' => $log->payload['referral'] ?? null,
                'free_window_expires_at' => $freeWindowExpiresAt,
                'tags' => is_array($tags) ? array_values($tags) : [],
            ];
        })->values();

        return response()->json(['conversations' => $conversations]);
    }

    /**
     * Get chat messages thread for a specific customer phone number.
     */
    public function getChatMessages(Request $request, int $id)
    {
        $user = $request->user();
        $businessQuery = WhatsappBusiness::where('id', $id);
        if (!$user->isAdmin()) {
            $businessQuery->where('user_id', $user->id);
        }
        $business = $businessQuery->firstOrFail();

        $phone = $request->query('phone');
        if (!$phone) {
            return response()->json(['messages' => [], 'contact' => null, 'free_window' => null]);
        }

        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);

        $messages = WhatsappLog::where('whatsapp_business_id', $id)
            ->where(function ($q) use ($phone, $cleanPhone) {
                $q->where('recipient_phone', $phone)
                  ->orWhere('recipient_phone', $cleanPhone)
                  ->orWhere('recipient_phone', '+' . $cleanPhone);
            })
            ->with(['account:id,name,phone_number_id'])
            ->orderBy('created_at', 'asc')
            ->take(500)
            ->get()
            ->map(function ($log) {
                $hasReferral = !empty($log->payload['referral']);
                $hoursWindow = $hasReferral ? 72 : 24;

                return [
                    'id' => $log->id,
                    'recipient_phone' => $log->recipient_phone,
                    'channel' => $log->channel ?? 'whatsapp',
                    'cost_charged' => (float) $log->cost_charged,
                    'message_type' => $log->message_type,
                    'message_body' => $log->message_body,
                    'status' => $log->status,
                    'direction' => $log->direction ?? ($log->status === 'inbound' ? 'inbound' : 'outbound'),
                    'meta_message_id' => $log->meta_message_id,
                    'error_message' => $log->error_message,
                    'referral' => $log->payload['referral'] ?? null,
                    'payload' => $log->payload,
                    'created_at' => $log->created_at->toIso8601String(),
                    'account_name' => $log->account?->name,
                    'free_window_expires_at' => $log->direction === 'inbound' ? $log->created_at->addHours($hoursWindow)->toIso8601String() : null,
                ];
            });

        $contact = WhatsappContact::whereHas('group', function ($q) use ($id) {
                $q->where('whatsapp_business_id', $id);
            })
            ->where(function ($q) use ($phone, $cleanPhone) {
                $q->where('phone', $phone)
                  ->orWhere('phone', $cleanPhone)
                  ->orWhere('phone', '+' . $cleanPhone);
            })
            ->first();

        // Determine active free window from last inbound message
        $lastInbound = $messages->where('direction', 'inbound')->last();
        $freeWindowInfo = null;
        if ($lastInbound) {
            $isAd = !empty($lastInbound['referral']);
            $expiresAt = \Carbon\Carbon::parse($lastInbound['created_at'])->addHours($isAd ? 72 : 24);
            $freeWindowInfo = [
                'type' => $isAd ? 'ctwa_72h' : 'organic_24h',
                'expires_at' => $expiresAt->toIso8601String(),
                'is_active' => $expiresAt->isFuture(),
                'hours_total' => $isAd ? 72 : 24,
            ];
        }

        return response()->json([
            'messages' => $messages,
            'contact' => $contact ? [
                'name' => $contact->name,
                'phone' => $contact->phone,
                'group_name' => $contact->group?->name,
                'custom_fields' => $contact->custom_fields,
            ] : null,
            'free_window' => $freeWindowInfo,
        ]);
    }

    /**
     * Get CTWA Meta Ads Performance analytics stats.
     */
    public function getAdPerformanceStats(Request $request, int $id)
    {
        $user = $request->user();
        $businessQuery = WhatsappBusiness::where('id', $id);
        if (!$user->isAdmin()) {
            $businessQuery->where('user_id', $user->id);
        }
        $business = $businessQuery->firstOrFail();

        $adLogs = WhatsappLog::where('whatsapp_business_id', $id)
            ->where('direction', 'inbound')
            ->whereNotNull('payload->referral')
            ->orderBy('created_at', 'desc')
            ->get();

        $statsByAd = [];
        foreach ($adLogs as $log) {
            $referral = $log->payload['referral'] ?? null;
            if (!$referral) continue;

            $sourceId = $referral['source_id'] ?? 'unknown_ad';
            $headline = $referral['headline'] ?? 'Meta CTWA Campaign';

            if (!isset($statsByAd[$sourceId])) {
                $statsByAd[$sourceId] = [
                    'source_id' => $sourceId,
                    'headline' => $headline,
                    'body' => $referral['body'] ?? null,
                    'total_chats' => 0,
                    'unique_leads' => [],
                    'last_lead_at' => $log->created_at->toIso8601String(),
                ];
            }

            $statsByAd[$sourceId]['total_chats']++;
            if (!in_array($log->recipient_phone, $statsByAd[$sourceId]['unique_leads'])) {
                $statsByAd[$sourceId]['unique_leads'][] = $log->recipient_phone;
            }
        }

        $adPerformance = array_values(array_map(function ($item) {
            return [
                'source_id' => $item['source_id'],
                'headline' => $item['headline'],
                'body' => $item['body'],
                'total_chats' => $item['total_chats'],
                'unique_leads_count' => count($item['unique_leads']),
                'last_lead_at' => $item['last_lead_at'],
            ];
        }, $statsByAd));

        return response()->json([
            'total_ctwa_leads' => count($adLogs),
            'active_ads_count' => count($adPerformance),
            'ads' => $adPerformance,
        ]);
    }

    /**
     * Send direct chat message via CRM Chat UI (Supports text, templates, images, documents, interactive buttons).
     */
    public function sendChatMessage(Request $request, int $id)
    {
        $user = $request->user();
        $businessQuery = WhatsappBusiness::where('id', $id);
        if (!$user->isAdmin()) {
            $businessQuery->where('user_id', $user->id);
        }
        $business = $businessQuery->firstOrFail();

        $validated = $request->validate([
            'whatsapp_account_id' => ['required', 'exists:whatsapp_accounts,id'],
            'recipient_phone' => ['required', 'string', 'max:50'],
            'message_body' => ['nullable', 'string', 'max:4096'],
            'message_type' => ['nullable', 'string', 'in:text,template,image,document,audio,video,interactive'],
            'template_id' => ['nullable', 'integer'],
            'template_name' => ['nullable', 'string'],
            'template_language' => ['nullable', 'string'],
            'template_components' => ['nullable', 'array'],
            'media_file' => ['nullable', 'file', 'max:16384'], // Max 16MB media
            'media_url' => ['nullable', 'string', 'max:1024'],
            'caption' => ['nullable', 'string', 'max:1024'],
            'buttons' => ['nullable', 'array', 'max:3'],
        ]);

        $account = WhatsappAccount::where('id', $validated['whatsapp_account_id'])
            ->where('whatsapp_business_id', $id)
            ->firstOrFail();

        $messageType = $validated['message_type'] ?? 'text';
        $templateData = null;
        $extraData = [];

        if ($messageType === 'template') {
            $templateModel = null;
            if (!empty($validated['template_id'])) {
                $templateModel = \Modules\WhatsappSender\Models\WhatsappTemplate::find($validated['template_id']);
            }
            if (!$templateModel && !empty($validated['template_name'])) {
                $templateQuery = \Modules\WhatsappSender\Models\WhatsappTemplate::where('whatsapp_business_id', $id)
                    ->where('name', $validated['template_name']);
                if (!empty($validated['template_language'])) {
                    $templateQuery->where('language', $validated['template_language']);
                }
                $templateModel = $templateQuery->first() ?? \Modules\WhatsappSender\Models\WhatsappTemplate::where('name', $validated['template_name'])->first();
            }

            $templateName = $validated['template_name'] ?? ($templateModel ? $templateModel->name : 'hello_world');
            $templateLanguage = $validated['template_language'] ?? ($templateModel ? $templateModel->language : 'en_US');

            $templateData = [
                'name' => $templateName,
                'language' => $templateLanguage,
            ];
            if (!empty($validated['template_components'])) {
                $templateData['components'] = $validated['template_components'];
            }
        }

        // Handle Media Upload if provided
        if (in_array($messageType, ['image', 'document', 'audio', 'video'])) {
            if ($request->hasFile('media_file')) {
                $uploadRes = $this->whatsappService->uploadMedia($account, $request->file('media_file'), $messageType);
                $extraData['link'] = $uploadRes['url'];
                $extraData['filename'] = $uploadRes['filename'];
            } elseif (!empty($validated['media_url'])) {
                $extraData['link'] = $validated['media_url'];
                $extraData['filename'] = basename(parse_url($validated['media_url'], PHP_URL_PATH)) ?: 'file';
            }
            if (!empty($validated['caption'])) {
                $extraData['caption'] = $validated['caption'];
            }
        }

        // Handle Interactive Buttons
        if ($messageType === 'interactive' && !empty($validated['buttons'])) {
            $extraData['buttons'] = $validated['buttons'];
        }

        $messageBody = $validated['message_body'] ?? '';
        if ($messageType === 'template' && empty($messageBody)) {
            $messageBody = "Template: " . ($validated['template_name'] ?? 'Message');
        } elseif (in_array($messageType, ['image', 'document', 'audio', 'video']) && empty($messageBody)) {
            $messageBody = !empty($extraData['caption']) ? $extraData['caption'] : "Sent a " . $messageType;
        }

        $result = $this->whatsappService->sendMessage(
            $account,
            $validated['recipient_phone'],
            $messageBody,
            $messageType,
            $templateData,
            $extraData
        );

        if ($result['success']) {
            if (isset($result['log_id'])) {
                WhatsappLog::where('id', $result['log_id'])->update(['direction' => 'outbound']);
            }

            $log = WhatsappLog::find($result['log_id'] ?? 0);

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully!',
                'log' => $log ? [
                    'id' => $log->id,
                    'recipient_phone' => $log->recipient_phone,
                    'channel' => $log->channel ?? 'whatsapp',
                    'cost_charged' => (float) $log->cost_charged,
                    'message_type' => $log->message_type,
                    'message_body' => $log->message_body,
                    'status' => $log->status,
                    'direction' => 'outbound',
                    'meta_message_id' => $log->meta_message_id,
                    'error_message' => null,
                    'created_at' => $log->created_at->toIso8601String(),
                    'account_name' => $account->name,
                ] : null,
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result['error'] ?? 'Message delivery failed.',
        ], 422);
    }

    /**
     * Get saved Canned Quick Replies for a business.
     */
    public function getQuickReplies(Request $request, int $id)
    {
        $user = $request->user();
        $businessQuery = WhatsappBusiness::where('id', $id);
        if (!$user->isAdmin()) {
            $businessQuery->where('user_id', $user->id);
        }
        $business = $businessQuery->firstOrFail();

        $defaultReplies = [
            ['shortcut' => '/welcome', 'title' => 'Welcome Greeting', 'message' => 'Hello! Welcome to our official WhatsApp support. How can we help you today? 👋'],
            ['shortcut' => '/hours', 'title' => 'Working Hours', 'message' => 'Our business hours are Sunday to Thursday, 9:00 AM - 6:00 PM (Cairo Time).'],
            ['shortcut' => '/support', 'title' => 'Technical Support', 'message' => 'Our technical specialist is reviewing your inquiry and will reply shortly.'],
            ['shortcut' => '/pricing', 'title' => 'Pricing & Plans', 'message' => 'You can view our complete service plans and pricing on our website: https://musoftwares.com'],
            ['shortcut' => '/thanks', 'title' => 'Thank You', 'message' => 'Thank you for reaching out to us! Have a wonderful day.'],
        ];

        $metadata = $business->metadata ?? [];
        $quickReplies = $metadata['quick_replies'] ?? $defaultReplies;

        return response()->json([
            'success' => true,
            'quick_replies' => $quickReplies,
        ]);
    }

    /**
     * Store or update a Canned Quick Reply.
     */
    public function storeQuickReply(Request $request, int $id)
    {
        $user = $request->user();
        $businessQuery = WhatsappBusiness::where('id', $id);
        if (!$user->isAdmin()) {
            $businessQuery->where('user_id', $user->id);
        }
        $business = $businessQuery->firstOrFail();

        $validated = $request->validate([
            'shortcut' => ['required', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:100'],
            'message' => ['required', 'string', 'max:2048'],
        ]);

        $shortcut = str_starts_with($validated['shortcut'], '/') ? $validated['shortcut'] : '/' . $validated['shortcut'];

        $metadata = $business->metadata ?? [];
        $quickReplies = $metadata['quick_replies'] ?? [];

        // Remove existing with same shortcut if updating
        $quickReplies = array_values(array_filter($quickReplies, fn($q) => ($q['shortcut'] ?? '') !== $shortcut));
        $quickReplies[] = [
            'shortcut' => $shortcut,
            'title' => $validated['title'],
            'message' => $validated['message'],
        ];

        $metadata['quick_replies'] = $quickReplies;
        $business->update(['metadata' => $metadata]);

        return response()->json([
            'success' => true,
            'message' => 'Quick reply saved successfully!',
            'quick_replies' => $quickReplies,
        ]);
    }

    /**
     * Delete a Canned Quick Reply.
     */
    public function deleteQuickReply(Request $request, int $id, string $shortcut)
    {
        $user = $request->user();
        $businessQuery = WhatsappBusiness::where('id', $id);
        if (!$user->isAdmin()) {
            $businessQuery->where('user_id', $user->id);
        }
        $business = $businessQuery->firstOrFail();

        $cleanShortcut = urldecode($shortcut);
        if (!str_starts_with($cleanShortcut, '/')) {
            $cleanShortcut = '/' . $cleanShortcut;
        }

        $metadata = $business->metadata ?? [];
        $quickReplies = $metadata['quick_replies'] ?? [];
        $quickReplies = array_values(array_filter($quickReplies, fn($q) => ($q['shortcut'] ?? '') !== $cleanShortcut));

        $metadata['quick_replies'] = $quickReplies;
        $business->update(['metadata' => $metadata]);

        return response()->json([
            'success' => true,
            'message' => 'Quick reply removed.',
            'quick_replies' => $quickReplies,
        ]);
    }

    /**
     * Update Contact CRM data (Name, Tags, Private Internal Notes).
     */
    public function updateContactCrm(Request $request, int $id)
    {
        $user = $request->user();
        $businessQuery = WhatsappBusiness::where('id', $id);
        if (!$user->isAdmin()) {
            $businessQuery->where('user_id', $user->id);
        }
        $business = $businessQuery->firstOrFail();

        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:50'],
            'name' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'array'],
            'internal_notes' => ['nullable', 'string', 'max:4096'],
        ]);

        $cleanPhone = preg_replace('/[^0-9]/', '', $validated['phone']);

        // Find or create Contact under the business's default group
        $defaultGroup = \Modules\WhatsappSender\Models\WhatsappContactGroup::firstOrCreate(
            ['whatsapp_business_id' => $business->id, 'name' => 'CRM Direct Inquiries'],
            ['user_id' => $business->user_id, 'description' => 'Automated group for live chat contacts']
        );

        $contact = WhatsappContact::where('phone', $cleanPhone)
            ->whereHas('group', fn($q) => $q->where('whatsapp_business_id', $business->id))
            ->first();

        if (!$contact) {
            $contact = new WhatsappContact([
                'whatsapp_contact_group_id' => $defaultGroup->id,
                'phone' => $cleanPhone,
            ]);
        }

        if (!empty($validated['name'])) {
            $contact->name = trim($validated['name']);
        }

        $customFields = $contact->custom_fields ?? [];
        if (isset($validated['tags'])) {
            $customFields['tags'] = array_values(array_unique(array_filter($validated['tags'])));
        }
        if (isset($validated['internal_notes'])) {
            $customFields['internal_notes'] = $validated['internal_notes'];
        }

        $contact->custom_fields = $customFields;
        $contact->save();

        return response()->json([
            'success' => true,
            'message' => 'Contact CRM details updated.',
            'contact' => [
                'name' => $contact->name,
                'phone' => $contact->phone,
                'group_name' => $contact->group?->name,
                'custom_fields' => $contact->custom_fields,
            ],
        ]);
    }

    /**
     * Get WhatsApp Business Profile & Health metrics for an account.
     */
    public function getAccountProfile(Request $request, int $id)
    {
        $user = $request->user();
        $query = WhatsappAccount::where('id', $id);
        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }
        $account = $query->firstOrFail();

        $profileResult = $this->whatsappService->getBusinessProfile($account);
        $healthResult = $this->whatsappService->getPhoneHealthAndLimits($account);

        return response()->json([
            'success' => true,
            'account' => [
                'id' => $account->id,
                'name' => $account->name,
                'phone_number_id' => $account->phone_number_id,
                'waba_id' => $account->waba_id,
                'status' => $account->status,
                'display_phone_number' => $account->display_phone_number,
                'metadata' => $account->metadata,
            ],
            'profile' => $profileResult['data'] ?? ($account->metadata['business_profile'] ?? null),
            'health' => $healthResult['data'] ?? null,
            'is_sandbox' => $this->whatsappService->isSandboxToken($account->access_token),
        ]);
    }

    /**
     * Update WhatsApp Business Profile fields.
     */
    public function updateAccountProfile(Request $request, int $id)
    {
        $user = $request->user();
        $query = WhatsappAccount::where('id', $id);
        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }
        $account = $query->firstOrFail();

        $validated = $request->validate([
            'about' => ['nullable', 'string', 'max:139'],
            'description' => ['nullable', 'string', 'max:512'],
            'address' => ['nullable', 'string', 'max:256'],
            'email' => ['nullable', 'email', 'max:255'],
            'vertical' => ['nullable', 'string', 'max:50'],
            'websites' => ['nullable', 'array', 'max:2'],
            'websites.*' => ['nullable', 'string', 'max:255'],
        ]);

        $result = $this->whatsappService->updateBusinessProfile($account, $validated);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'WhatsApp Business Profile updated successfully on Meta Cloud API.',
                'profile' => $result['data'],
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result['error'] ?? 'Failed to update business profile.',
        ], 422);
    }

    /**
     * Upload & update WhatsApp Business Profile picture.
     */
    public function uploadAccountPhoto(Request $request, int $id)
    {
        $user = $request->user();
        $query = WhatsappAccount::where('id', $id);
        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }
        $account = $query->firstOrFail();

        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'], // Max 5MB
        ]);

        $result = $this->whatsappService->updateProfilePicture($account, $request->file('photo'));

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'WhatsApp profile picture updated successfully!',
                'profile_picture_url' => $result['profile_picture_url'],
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result['error'] ?? 'Failed to upload profile picture.',
        ], 422);
    }

    /**
     * Delete WhatsApp Business Profile picture.
     */
    public function deleteAccountPhoto(Request $request, int $id)
    {
        $user = $request->user();
        $query = WhatsappAccount::where('id', $id);
        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }
        $account = $query->firstOrFail();

        $result = $this->whatsappService->deleteProfilePicture($account);

        return response()->json([
            'success' => true,
            'message' => 'WhatsApp profile picture removed.',
        ]);
    }

    /**
     * Set / Update Two-Step Verification PIN (6 digits).
     */
    public function updateAccountPin(Request $request, int $id)
    {
        $user = $request->user();
        $query = WhatsappAccount::where('id', $id);
        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }
        $account = $query->firstOrFail();

        $validated = $request->validate([
            'pin' => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
        ]);

        $result = $this->whatsappService->setTwoStepVerificationPin($account, $validated['pin']);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Two-Step Verification PIN updated successfully on Meta Cloud API.',
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result['error'] ?? 'Failed to update Two-Step PIN.',
        ], 422);
    }

    /**
     * Sync Health & Quality Status for an account.
     */
    public function syncAccountHealth(Request $request, int $id)
    {
        $user = $request->user();
        $query = WhatsappAccount::where('id', $id);
        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }
        $account = $query->firstOrFail();

        $healthResult = $this->whatsappService->getPhoneHealthAndLimits($account);

        if ($healthResult['success']) {
            return response()->json([
                'success' => true,
                'message' => 'WhatsApp account health and limits synchronized with Meta.',
                'health' => $healthResult['data'],
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $healthResult['error'] ?? 'Failed to sync phone health status.',
        ], 422);
    }
}

