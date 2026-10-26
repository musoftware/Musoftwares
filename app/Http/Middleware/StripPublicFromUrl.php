<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The app lives one folder above its public/ directory on the host, so /public/dashboard (and
 * /public/index.php/...) also reach Laravel and every link on that page keeps the /public prefix.
 * Send those addresses to the clean URL. Only GET and HEAD are redirected so a webhook that was
 * registered with a /public address keeps working.
 */
class StripPublicFromUrl
{
    public function handle(Request $request, Closure $next): Response
    {
        $uri = (string) $request->server('REQUEST_URI', '');

        if ($request->isMethodCacheable() && preg_match('#^/public(?:/index\.php)?(?=/|\?|$)#i', $uri, $match)) {
            $clean = substr($uri, strlen($match[0]));
            $clean = $clean === '' || $clean[0] === '?' ? '/'.$clean : $clean;

            return redirect()->to($request->getSchemeAndHttpHost().$clean, 301);
        }

        return $next($request);
    }
}
