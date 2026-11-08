<?php

namespace App\Http\Controllers;

use App\Services\AiAssistant\AssistantChat;
use App\Services\AiAssistant\AssistantLimiter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class AiAssistantController extends Controller
{
    private const MAX_MESSAGE_LENGTH = 500;

    private const DEVICE_COOKIE = 'ai_assistant_device';

    private const ONE_YEAR_IN_MINUTES = 525600;

    public function __construct(private readonly AssistantChat $chat) {}

    public function history(): JsonResponse
    {
        return response()->json(['messages' => $this->chat->history()]);
    }

    public function reset(): JsonResponse
    {
        $this->chat->reset();

        return response()->json(['messages' => []]);
    }

    public function message(Request $request, AssistantLimiter $limiter): JsonResponse
    {
        $validated = $request->validate(['message' => ['required', 'string', 'max:'.self::MAX_MESSAGE_LENGTH]]);

        if (! $limiter->allow($this->identities($request), (string) $request->ip())) {
            return response()->json(['reply' => AssistantChat::limitMessage()], 429);
        }

        try {
            return response()->json(['reply' => $this->chat->answer($validated['message'], $request->user())]);
        } catch (Throwable $e) {
            Log::error('AI assistant reply failed', ['user_id' => $request->user()?->id, 'exception' => $e::class, 'error' => $e->getMessage()]);

            return response()->json(['reply' => AssistantChat::fallbackMessage()], 503);
        }
    }

    /** @return list<string> */
    private function identities(Request $request): array
    {
        $account = $request->user()?->id;

        return array_values(array_filter(['device:'.$this->deviceId($request), $account ? "user:{$account}" : null]));
    }

    /** A random id kept in an encrypted cookie, so the same device is known after its IP changes. */
    private function deviceId(Request $request): string
    {
        $id = $request->cookie(self::DEVICE_COOKIE);
        if (is_string($id) && Str::isUuid($id)) {
            return $id;
        }

        $id = (string) Str::uuid();
        Cookie::queue(self::DEVICE_COOKIE, $id, self::ONE_YEAR_IN_MINUTES);

        return $id;
    }
}
