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

        $accounts = WhatsappAccount::where('whatsapp_business_id', $id)
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
        $status = 'active';
        if ($verification['valid']) {
            $metadata = $verification['data'];
            if (isset($metadata['status']) && $metadata['status'] !== 'CONNECTED') {
                $status = 'unregistered';
            }
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
        $validated = $request->validate([
            'pin' => ['required', 'string', 'size:6', 'regex:/^[0-9]+$/'],
        ]);

        $account = WhatsappAccount::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        $result = $this->whatsappService->registerPhoneNumber($account, $validated['pin']);

        if ($result['success']) {
            return redirect()->back()->with('success', $result['message'] ?? 'Phone number registered and activated on Meta Cloud API successfully!');
        }

        return redirect()->back()->with('error', $result['error'] ?? 'Failed to register phone number.');
    }

    /**
     * Sync WhatsApp account status and metadata from Meta Graph API.
     */
    public function syncAccountStatus(Request $request, int $id): RedirectResponse
    {
        $account = WhatsappAccount::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

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

        $result = $this->whatsappService->testAccountConnection($account);

        if ($result['success']) {
            return redirect()->back()
                ->with('success', "Meta API Connection Test Completed! {$result['message']}")
                ->with('meta_response', $result['data']);
        }

        return redirect()->back()
            ->with('error', $result['error'] ?? 'Meta API Connection Test Failed.')
            ->with('meta_response', $result['data'] ?? null);
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
}
