<?php

namespace App\Services\AiAssistant;

use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Every message costs money, so one message counts against each thing that identifies the sender:
 * the device cookie, the account and the IP. New cookies are caught by the IP, a new IP is caught
 * by the cookie or the account. The site-wide limit covers anyone who changes all of them.
 */
class AssistantLimiter
{
    private const MINUTE = 60;

    private const DAY = 86400;

    /** @param list<string> $identities the device cookie and, when signed in, the account */
    public function allow(array $identities, string $ip): bool
    {
        $limiter = new RateLimiter(Cache::store(config('services.anthropic.assistant_limiter_store')));
        $windows = $this->windows($identities, $ip);

        foreach ($windows as [$key, $max]) {
            if ($limiter->tooManyAttempts($key, $max)) {
                Log::warning('AI assistant limit reached', ['window' => $key]);

                return false;
            }
        }

        foreach ($windows as [$key, , $seconds]) {
            $limiter->hit($key, $seconds);
        }

        return true;
    }

    /**
     * @param  list<string>  $identities
     * @return list<array{0: string, 1: int, 2: int}>
     */
    private function windows(array $identities, string $ip): array
    {
        $perMinute = config('services.anthropic.assistant_visitor_per_minute');
        $perDay = config('services.anthropic.assistant_visitor_per_day');
        $windows = [
            ["ai-assistant:minute:ip:{$ip}", $perMinute, self::MINUTE],
            ["ai-assistant:day:ip:{$ip}", config('services.anthropic.assistant_ip_per_day'), self::DAY],
            ['ai-assistant:site', config('services.anthropic.assistant_site_per_day'), self::DAY],
        ];

        foreach ($identities as $identity) {
            $windows[] = ["ai-assistant:minute:{$identity}", $perMinute, self::MINUTE];
            $windows[] = ["ai-assistant:day:{$identity}", $perDay, self::DAY];
        }

        return $windows;
    }
}
