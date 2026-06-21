<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\BlockedIp;
use Illuminate\Support\Facades\Cache;

class SecurityEnforcement
{
    protected $maliciousUriPatterns = [
        '../',
        '..\\',
        '.env',
        'wp-admin',
        'wp-login',
        'eval(',
        'base64_decode(',
        'phpinfo()',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();

        // 1. Check if IP is already blocked
        $isBlocked = Cache::remember("blocked_ip:{$ip}", 3600, function () use ($ip) {
            $block = BlockedIp::where('ip_address', $ip)->first();
            if (!$block) return false;
            
            if ($block->blocked_until && now()->greaterThan($block->blocked_until)) {
                $block->delete();
                return false;
            }
            return true;
        });

        if ($isBlocked) {
            abort(403, 'Your IP address has been blocked due to suspicious activity.');
        }

        // 2. Check for malicious patterns in the request URI
        $uri = urldecode($request->getRequestUri());
        foreach ($this->maliciousUriPatterns as $pattern) {
            if (stripos($uri, $pattern) !== false) {
                $this->blockIp($ip, "Malicious URL pattern detected: {$pattern}");
                abort(403, 'Suspicious activity detected.');
            }
        }

        return $next($request);
    }

    protected function blockIp($ip, $reason)
    {
        BlockedIp::firstOrCreate(
            ['ip_address' => $ip],
            ['reason' => $reason, 'blocked_until' => now()->addDays(7)]
        );
        Cache::put("blocked_ip:{$ip}", true, 3600);
    }
}
