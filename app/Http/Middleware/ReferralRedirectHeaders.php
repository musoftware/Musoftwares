<?php

namespace App\Http\Middleware;

use App\Models\UserReferral;
use App\Services\ReferralService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sets the X-Frame-Options / Referrer-Policy response headers for the
 * Facebook in-app browser, and writes the view-dedupe cookie so subsequent
 * visits to /r/{slug} don't double-count.
 *
 * Previously these headers were set inline from ReferralService::processReferralRedirect
 * via header() — that broke under middleware ordering and queue/console runs.
 */
class ReferralRedirectHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $ua = $request->userAgent() ?? '';
        if (str_contains($ua, 'FBAV')) {
            $response->headers->set('X-Frame-Options', 'DENY');
            $response->headers->set('Referrer-Policy', 'origin');
        }

        // Write view-dedupe cookie if a referral was resolved this request.
        $ref = $request->session()->get('referral');
        if ($ref) {
            $referral = UserReferral::resolveRef((string) $ref);
            if ($referral) {
                cookie()->queue(cookie()->forever(
                    ReferralService::VIEW_DEDUPE_COOKIE,
                    $referral->key
                ));
            }
        }

        return $response;
    }
}