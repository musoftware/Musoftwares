<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Returns a page's props as JSON only when the caller asks for it
 * (?format=json or an Accept: application/json header).
 *
 * Crawlers are never detected by User-Agent: search engines must get the same
 * HTML a visitor gets, or Google treats the page as cloaked and drops it.
 */
class AiCrawlerContentNegotiation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->shouldServeJson($request)) {
            return $response;
        }

        if ($response instanceof InertiaResponse) {
            return $this->json($response->toResponse($request)->getOriginalContent()->getData()['page']['props'] ?? []);
        }

        $content = $response->getContent();
        if (! is_string($content) || ! preg_match('/data-page="([^"]+)"/', $content, $matches)) {
            return $response;
        }

        $pageData = json_decode(htmlspecialchars_decode($matches[1], ENT_QUOTES), true);

        return $this->json($pageData['props'] ?? $pageData);
    }

    private function shouldServeJson(Request $request): bool
    {
        if ($request->header('X-Inertia')) {
            return false;
        }

        return $request->query('format') === 'json' || $request->wantsJson();
    }

    private function json(mixed $data): Response
    {
        return response()->json(
            $data,
            200,
            ['Content-Type' => 'application/json; charset=utf-8'],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        );
    }
}
