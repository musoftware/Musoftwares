<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

class AiCrawlerContentNegotiation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Check if request is coming from an AI crawler or explicitly requesting JSON
        if ($this->shouldServeJson($request)) {

            // 1. If the controller returns an Inertia Response directly
            if ($response instanceof InertiaResponse) {
                return response()->json(
                    $response->toResponse($request)->getOriginalContent()->getData()['page']['props'] ?? [],
                    200,
                    ['Content-Type' => 'application/json; charset=utf-8'],
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
                );
            }

            // 2. If the response was already rendered to standard HTML
            $content = $response->getContent();
            if (is_string($content) && str_contains($content, 'data-page="')) {
                preg_match('/data-page="([^"]+)"/', $content, $matches);
                if (!empty($matches[1])) {
                    $pageData = json_decode(htmlspecialchars_decode($matches[1], ENT_QUOTES), true);
                    return response()->json(
                        $pageData['props'] ?? $pageData,
                        200,
                        ['Content-Type' => 'application/json; charset=utf-8'],
                        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
                    );
                }
            }
        }

        return $response;
    }

    private function shouldServeJson(Request $request): bool
    {
        // If the user or bot explicitly requests format via query or headers
        if ($request->query('format') === 'json' || $request->wantsJson()) {
            // Exclude internal Inertia navigation requests
            if (!$request->header('X-Inertia')) {
                return true;
            }
        }

        // List of supported AI crawler and bot user-agents
        $userAgent = strtolower($request->header('User-Agent', ''));

        $aiBots = [
            'google-extended',
            'googlebot',
            'gptbot',
            'chatgpt-user',
            'claudebot',
            'claude-web',
            'perplexitybot',
            'bytespider',
            'cohere-ai',
            'applebot-extended',
            'diffbot',
            'meta-externalagent',
        ];

        foreach ($aiBots as $bot) {
            if (str_contains($userAgent, $bot)) {
                return true;
            }
        }

        return false;
    }
}
