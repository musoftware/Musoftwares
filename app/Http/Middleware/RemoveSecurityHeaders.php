<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RemoveSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Remove PHP/Laravel footprint headers
        if (method_exists($response, 'header')) {
            $response->header('X-Powered-By', '');
            $response->headers->remove('X-Powered-By');
        }

        // Allow iframes for SMS Gateway checkout pages
        if ($request->is('pay/*') || $request->is('sms-pay/*') || $request->is('sms-payment-gateway/checkout/*')) {
            $response->headers->remove('X-Frame-Options');
        }

        return $response;
    }
}
