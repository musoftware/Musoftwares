<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Privacy\ConsentAuditService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforcePrivacyWallMiddleware
{
    private const EXEMPT_PATTERNS = [
        'privacy-policy',
        'terms-of-service',
        'cookie-policy',
        'privacy',
        'terms',
        'cookies',
        'legal/*',
        'privacy/consent/*',
        'unsubscribe/*',
        'winback/unsubscribe/*',
        'logout',
        'up',
        'sitemap*',
        'sitemaps/*',
        'robots.txt',
        '.well-known/*',
        'llms*.txt',
        'api/*',
        '*/webhook*',
    ];

    private const CRAWLER_UA_REGEX = '/Googlebot|Bingbot|Slurp|DuckDuckBot|Baiduspider|YandexBot|facebookexternalhit|Twitterbot|LinkedInBot|Applebot|GPTBot|ClaudeBot|PerplexityBot/i';

    public function __construct(
        private readonly ConsentAuditService $consentService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isExempt($request)) {
            return $next($request);
        }

        $state = $this->consentService->resolveRequestConsentState($request);
        if (! $state['isBlocked']) {
            return $next($request);
        }

        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return response()->json([
                'status' => 'error',
                'blocked' => true,
                'message' => __('privacy_compliance.blocked_title'),
            ], 403);
        }

        return response()->view('public.legal.blocked', [], 403);
    }

    private function isExempt(Request $request): bool
    {
        if ($request->is(...self::EXEMPT_PATTERNS)) {
            return true;
        }

        $ua = (string) $request->userAgent();

        return $ua !== '' && preg_match(self::CRAWLER_UA_REGEX, $ua) === 1;
    }
}
