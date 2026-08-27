<?php

namespace Modules\WhatsappSender\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Modules\WhatsappSender\Models\TelegramBot;
use Modules\WhatsappSender\Models\WhatsappBusiness;
use Modules\WhatsappSender\Services\TelegramBotService;
use Illuminate\Validation\ValidationException;

class TelegramBotController extends Controller
{
    public function __construct(
        protected TelegramBotService $telegramService
    ) {}

    /**
     * Store a new Telegram Bot.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'whatsapp_business_id' => ['required', 'exists:whatsapp_businesses,id'],
            'token' => ['required', 'string'],
        ]);

        $user = $request->user();
        $businessQuery = WhatsappBusiness::where('id', $validated['whatsapp_business_id']);
        if (!$user->isAdmin()) {
            $businessQuery->where('user_id', $user->id);
        }
        $business = $businessQuery->firstOrFail();

        // Query Telegram API to verify token and fetch details
        $details = $this->telegramService->getBotDetails($validated['token']);

        if (!$details['success']) {
            throw ValidationException::withMessages([
                'token' => 'Telegram API Error: ' . $details['error'],
            ]);
        }

        // Create Bot
        $bot = TelegramBot::create([
            'whatsapp_business_id' => $business->id,
            'name' => $details['name'],
            'username' => $details['username'],
            'token' => $validated['token'],
            'status' => 'active',
        ]);

        // Register Webhook
        $webhook = $this->telegramService->setWebhook($bot);

        if (!$webhook['success']) {
            $bot->forceDelete();
            throw ValidationException::withMessages([
                'token' => 'Failed to register webhook with Telegram: ' . $webhook['error'],
            ]);
        }

        return redirect()->route('whatsapp.businesses.workspace', $business->id)
            ->with('success', "Telegram Bot @{$details['username']} registered successfully!");
    }

    /**
     * Delete a Telegram Bot.
     */
    public function destroy(Request $request, int $id): RedirectResponse
    {
        $user = $request->user();
        $bot = TelegramBot::findOrFail($id);
        $businessQuery = WhatsappBusiness::where('id', $bot->whatsapp_business_id);
        if (!$user->isAdmin()) {
            $businessQuery->where('user_id', $user->id);
        }
        $business = $businessQuery->firstOrFail();

        // Delete Webhook from Telegram
        $this->telegramService->deleteWebhook($bot);

        $bot->delete();

        return redirect()->route('whatsapp.businesses.workspace', $business->id)
            ->with('success', 'Telegram Bot deleted successfully.');
    }
}
