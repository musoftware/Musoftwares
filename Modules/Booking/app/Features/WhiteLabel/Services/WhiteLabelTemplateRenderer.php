<?php

namespace Modules\Booking\app\Features\WhiteLabel\Services;

use Modules\Booking\app\Features\WhiteLabel\Models\WhiteLabelTemplate;

class WhiteLabelTemplateRenderer
{
    /**
     * Renders a custom white label template substituting variables.
     */
    public function render(int $tenantId, string $type, array $variables, string $defaultBody = ''): string
    {
        $template = WhiteLabelTemplate::where('tenant_id', $tenantId)->where('type', $type)->first();
        
        $body = $template ? $template->body : $defaultBody;

        foreach ($variables as $key => $value) {
            $body = str_replace("{{ {$key} }}", $value, $body);
            // also handle without spaces
            $body = str_replace("{{{$key}}}", $value, $body);
        }

        return $body;
    }
}
