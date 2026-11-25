<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hides the PHP version and adds the browser security headers every page should carry (a security scan found them
 * missing on the live site). A header a route already set (for example a referral page's Referrer-Policy) is kept.
 */
class RemoveSecurityHeaders
{
    private const HEADERS = [
        'X-Content-Type-Options' => 'nosniff',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        // the support ticket form records voice notes, so the microphone stays allowed for this site only
        'Permissions-Policy' => 'camera=(), microphone=(self), geolocation=()',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // PHP adds X-Powered-By itself (expose_php), outside the response object
        header_remove('X-Powered-By');
        $response->headers->remove('X-Powered-By');
        $this->addSecurityHeaders($request, $response);

        // Allow iframes for SMS Gateway checkout pages and white label partner stores
        if ($request->is('pay/*') || $request->is('sms-pay/*') || $request->is('sms-payment-gateway/checkout/*') || $request->is('shop/*')) {
            $response->headers->remove('X-Frame-Options');
        }

        return $response;
    }

    private function addSecurityHeaders(Request $request, Response $response): void
    {
        foreach (self::HEADERS as $name => $value) {
            if (! $response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }
    }
}
