<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\UserEmbedKey;
use Illuminate\Support\Facades\Auth;

class VerifyEmbedKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $key = $request->input('embed_key') ?: $request->header('X-Embed-Key');

        if (!$key) {
            return response()->json(['error' => 'Embed key is missing.'], 401);
        }

        $embedKey = UserEmbedKey::where('key', $key)->where('is_active', true)->first();

        if (!$embedKey) {
            return response()->json(['error' => 'Invalid or inactive embed key.'], 401);
        }

        // Check referer domain if allowed_domains is set
        $referer = $request->headers->get('referer');
        if ($embedKey->allowed_domains && is_array($embedKey->allowed_domains) && count($embedKey->allowed_domains) > 0) {
            $allowed = false;
            if ($referer) {
                $host = parse_url($referer, PHP_URL_HOST);
                foreach ($embedKey->allowed_domains as $domain) {
                    if (str_contains($host, $domain)) {
                        $allowed = true;
                        break;
                    }
                }
            }
            if (!$allowed) {
                return response()->json(['error' => 'Domain not allowed.'], 403);
            }
        }

        // Allow iframe rendering
        $response = $next($request);
        if (method_exists($response, 'header')) {
            $response->header('X-Frame-Options', 'ALLOWALL');
        }

        return $response;
    }
}
