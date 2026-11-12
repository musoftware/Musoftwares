<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\Telegram\TelegramApi;
use App\Services\Telegram\TelegramLinks;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** The settings page links the account to the site's Telegram bot, sends a test message, or unlinks it. */
class TelegramLinkController extends Controller
{
    public function __construct(private readonly TelegramApi $api, private readonly TelegramLinks $links) {}

    /** Also what the page asks every few seconds after opening the bot, until the Start has linked the account. */
    public function show(Request $request): JsonResponse
    {
        return response()->json($this->state($request));
    }

    /** The bot's link with a one-time code; the page opens it and the user presses Start. */
    public function link(Request $request): JsonResponse
    {
        $url = $this->links->linkUrl($request->user());
        abort_if($url === null, 409, __('client_home.settings.telegram_failed'));

        return response()->json(['url' => $url]);
    }

    public function test(Request $request): JsonResponse
    {
        $chatId = (string) $request->user()->telegram_chat_id;
        abort_if($chatId === '' || ! $this->api->send($chatId, __('client_home.settings.telegram_test_message'), [[__('client_home.settings.title'), route('client.settings')]]), 409, __('client_home.settings.telegram_failed'));

        return response()->json($this->state($request));
    }

    public function unlink(Request $request): JsonResponse
    {
        $this->links->unlink($request->user());

        return response()->json($this->state($request));
    }

    /** @return array{configured: bool, linked: bool, bot: string|null} */
    private function state(Request $request): array
    {
        return [
            'configured' => $this->api->configured(),
            'linked' => (string) $request->user()->refresh()->telegram_chat_id !== '',
            'bot' => $this->api->botUsername(),
        ];
    }
}
