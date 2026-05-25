<?php

namespace Modules\Booking\app\Features\Widget\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Booking\app\Features\Widget\Models\BookingWidget;

class ValidateWidgetDomain
{
    public function handle(Request $request, Closure $next)
    {
        $uuid = $request->route('uuid');
        
        $widget = BookingWidget::where('uuid', $uuid)->where('is_active', true)->first();

        if (!$widget) {
            return response()->json(['error' => 'Widget not found or deactivated.'], 404);
        }

        // Get origin domain of request (e.g. https://myclinic.com)
        $origin = $request->headers->get('origin');
        if (!$origin) {
            $origin = $request->headers->get('referer');
        }

        if ($origin) {
            $parsedUrl = parse_url($origin);
            $host = $parsedUrl['host'] ?? null;

            if ($host) {
                // Remove www.
                $host = preg_replace('/^www\./', '', $host);

                // Check if this domain is allowed for this specific widget
                $isAllowed = $widget->domains()->where('domain', $host)->exists();
                
                // Allow wildcard logic in a real scenario, but strict equality for now.
                if (!$isAllowed) {
                    return response()->json([
                        'error' => 'CORS Policy: Domain ' . $host . ' is not whitelisted for this widget.'
                    ], 403);
                }
            }
        }

        // Pass widget ID into request for downstream controllers
        $request->merge(['_widget_id' => $widget->id, '_tenant_id' => $widget->tenant_id]);

        return $next($request);
    }
}
