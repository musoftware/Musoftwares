<?php

namespace App\Http\Controllers;

use App\Services\Telegram\SiteBot;
use App\Services\Telegram\TelegramApi;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

/** Telegram delivers every message to the bot here, signed with the secret the site gave it when the webhook was set. */
class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request, TelegramApi $api, SiteBot $bot): Response
    {
        if (! hash_equals($api->webhookSecret(), (string) $request->header('X-Telegram-Bot-Api-Secret-Token'))) {
            return response()->noContent(403);
        }

        try {
            $bot->handle($request->all());
        } catch (Throwable $error) {
            // an answer Telegram sees as failed is sent again and again, so a problem is logged and the update is taken
            Log::error('Telegram update failed', ['error' => $error->getMessage()]);
        }

        return response()->noContent();
    }
}
