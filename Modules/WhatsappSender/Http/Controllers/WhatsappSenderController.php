<?php

namespace Modules\WhatsappSender\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\WhatsappSender\Models\WhatsappAccount;
use Modules\WhatsappSender\Models\WhatsappLog;
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

        $accounts = WhatsappAccount::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $logs = WhatsappLog::where('user_id', $user->id)
            ->with('account:id,name,phone_number_id')
            ->orderBy('created_at', 'desc')
            ->take(50)
            ->get();

        // Get or create API token for the user
        $apiToken = $user->tokens()->where('name', 'whatsapp-sender-api')->first()?->token
            ?? $user->createToken('whatsapp-sender-api')->plainTextToken;

        return Inertia::render('WhatsappSender/Index', [
            'accounts' => $accounts,
            'logs' => $logs,
            'apiToken' => $apiToken,
            'facebookLoginUrl' => route('whatsapp.auth.facebook'),
        ]);
    }

    /**
     * Store or update Meta API credentials manually.
     */
    public function storeAccount(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone_number_id' => ['required', 'string', 'max:255'],
            'waba_id' => ['nullable', 'string', 'max:255'],
            'access_token' => ['required', 'string'],
        ]);

        // Optional: verify credentials before saving
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
            return redirect()->route('whatsapp.index')->with('success', __('whatsapp-sender::messages.message_sent_successfully'));
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
