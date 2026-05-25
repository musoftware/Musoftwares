<?php

namespace Modules\Booking\app\Features\Widget\Services;

use Modules\Booking\app\Features\Widget\Models\BookingWidget;

class WidgetEmbedGenerator
{
    /**
     * Generates the raw JavaScript code that a tenant embeds on their external site.
     */
    public function generateJsPayload(BookingWidget $widget): string
    {
        $apiUrl = config('app.url') . "/api/v1/public/widgets/{$widget->uuid}";
        
        $js = <<<JS
(function() {
    console.log("Musoftware Booking Widget Initializing...");

    // Create container if not exists (for popup logic)
    var containerId = "musoftware-booking-widget";
    var container = document.getElementById(containerId);
    if(!container) {
        container = document.createElement("div");
        container.id = containerId;
        document.body.appendChild(container);
    }

    // Build the isolated iframe
    var iframe = document.createElement("iframe");
    iframe.src = "{$apiUrl}/ui";
    iframe.style.width = "100%";
    iframe.style.height = "600px";
    iframe.style.border = "none";
    iframe.style.borderRadius = "8px";
    iframe.style.boxShadow = "0 4px 6px rgba(0,0,0,0.1)";

    container.appendChild(iframe);

    // Register a view (analytics)
    fetch("{$apiUrl}/view", { method: "POST" }).catch(e => console.error(e));
})();
JS;

        return $js;
    }
}
