<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceFreelanceDomain
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->getHost() === 'lance.musoftwares.com') {
            $path = $request->path();

            // Allow the root path (handled by HomeController to render landing)
            if ($path === '/') {
                return $next($request);
            }

            // List of allowed route prefixes for the freelance domain
            $allowedPrefixes = [
                'freelance',
                'login',
                'register',
                'logout',
                'password',
                'verify',
                'email',
                'profile',
                'points',
                'subscriptions',
                'billing',
                'api',
                'sanctum',
                '_ignition',
                'livewire',
                'broadcasting'
            ];

            $isAllowed = false;
            foreach ($allowedPrefixes as $prefix) {
                if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                    $isAllowed = true;
                    break;
                }
            }

            if (!$isAllowed) {
                // If it's a known generic route like dashboard, redirect to freelance dashboard
                if ($path === 'dashboard' || $path === 'admin/dashboard') {
                    return redirect()->route('freelance.dashboard');
                }
                
                // For any other non-allowed route, either redirect to dashboard or abort
                // Redirecting to freelance dashboard provides a better user experience
                return redirect()->route('freelance.dashboard');
            }
        }

        return $next($request);
    }
}
