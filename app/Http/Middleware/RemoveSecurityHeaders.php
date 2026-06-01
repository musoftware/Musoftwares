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
        
        return $response;
    }
}
