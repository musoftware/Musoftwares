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

        // Ensure user has at least one Business Client profile
        $businesses = WhatsappBusiness::where('user_id', $user->id)
            ->withCount('accounts')
            ->orderBy('created_at', 'asc')
            ->get();

        if ($businesses->isEmpty()) {
            $defaultBiz = WhatsappBusiness::create([
                'user_id' => $user->id,
                'name' => 'Default Business Client',
                'wallet_balance' => 10.0000, // $10 free credit balance
                'currency' => 'USD',
                'per_message_fee' => 0.0010,
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

            $businesses = collect([$defaultBiz]);
        } else {
            foreach ($businesses as $biz) {
                if (empty($biz->webhook_verify_token)) {
                    $biz->update(['webhook_verify_token' => 'biz_wt_' . \Illuminate\Support\Str::random(24)]);
                }
            }
        }

        $accounts = WhatsappAccount::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $logs = WhatsappLog::where('user_id', $user->id)
            ->with(['account:id,name,phone_number_id', 'business:id,name'])
            ->orderBy('created_at', 'desc')
            ->take(50)
            ->get();

        $transactions = WhatsappTransaction::where('user_id', $user->id)
            ->with('business:id,name')
            ->orderBy('created_at', 'desc')
            ->take(50)
            ->get();

        // Get or create API token for the user
        $apiToken = $user->tokens()->where('name', 'whatsapp-sender-api')->first()?->token
            ?? $user->createToken('whatsapp-sender-api')->plainTextToken;

        $fbOauthToken = session('fb_oauth_token');
        if ($fbOauthToken) {
            session()->forget('fb_oauth_token');
        }

        // Webhook settings & token
        $webhookUrl = url('/api/v1/whatsapp/webhook');
        $webhookVerifyToken = \App\Models\AdminSettings::GetValue('whatsapp_webhook_verify_token', 'musoftware_whatsapp_verify_token_2026');

        return Inertia::render('WhatsappSender/Index', [
            'businesses' => $businesses,
            'accounts' => $accounts,
            'logs' => $logs,
            'transactions' => $transactions,
            'apiToken' => $apiToken,
            'facebookLoginUrl' => route('whatsapp.auth.facebook'),
            'fbOauthToken' => $fbOauthToken,
            'webhookUrl' => $webhookUrl,
            'webhookVerifyToken' => $webhookVerifyToken,
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

        return redirect()->route('whatsapp.index')->with('success', __('general.webhook_settings_saved_successfully') ?? 'Webhook verify token updated successfully!');
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
        if ($verification['valid']) {
            $metadata = $verification['data'];
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
                'status' => 'active',
                'metadata' => $metadata,
            ]
        );

        return redirect()->route('whatsapp.index')->with('success', __('whatsapp-sender::messages.account_saved_successfully'));
    }

    /**
     * Send a WhatsApp message via the web form.
     */
    public function sendMessage(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'whatsapp_account_id' => ['required', 'exists:whatsapp_accounts,id'],
            'recipient_phone' => ['required', 'string', 'max:50'],
            'message_body' => ['required', 'string', 'max:4096'],
            'message_type' => ['nullable', 'string', 'in:text,template'],
            'template_name' => ['nullable', 'string'],
            'template_language' => ['nullable', 'string'],
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
        }

        $result = $this->whatsappService->sendMessage(
            $account,
            $validated['recipient_phone'],
            $validated['message_body'],
            $validated['message_type'] ?? 'text',
            $templateData
        );

        if ($result['success']) {
            return redirect()->route('whatsapp.index')->with('success', "Message sent successfully! (Charged \${$result['cost_charged']} USD platform fee)");
        }

        return redirect()->route('whatsapp.index')->with('error', $result['error'] ?? __('whatsapp-sender::messages.message_failed'));
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

        return redirect()->route('whatsapp.index')->with('success', __('whatsapp-sender::messages.account_deleted_successfully'));
    }
}
