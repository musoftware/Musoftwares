<?php

namespace App\Services\ClientSystems;

use App\Models\ClientSystem;

/**
 * The prompt the owner pastes into an AI working on the client's project (any language). It holds this system's
 * keys, so it is shown only to the admin. The sales part is added only when the system pays a share of sales.
 */
final class IntegrationPrompt
{
    private const TEMPLATES = 'prompts/client-systems/';

    public function render(ClientSystem $system): string
    {
        $values = [
            '{{SYSTEM_NAME}}' => $system->name,
            '{{SYSTEM_ID}}' => $system->uuid,
            '{{API_BASE}}' => rtrim(url('/api/client-systems'), '/'),
            '{{API_FALLBACK}}' => rtrim((string) config('client_systems.fallback_api_base'), '/'),
            '{{API_KEY}}' => $system->api_key,
            '{{API_SECRET}}' => $system->api_secret,
            '{{PUBLIC_KEY}}' => $system->signing_public_key,
            '{{CURRENCY}}' => (string) $system->currency?->currency,
            '{{CHECK_EVERY_HOURS}}' => (string) config('client_systems.check_every_hours'),
            '{{OFFLINE_HOURS}}' => (string) config('client_systems.offline_hours'),
            '{{MAX_SALES}}' => (string) config('client_systems.max_sales_per_request'),
            '{{SALES_PERCENT}}' => rtrim(rtrim(number_format((float) $system->sales_percent, 2, '.', ''), '0'), '.'),
            '{{MODULE_HINT}}' => 'the licensing module and its calls',
        ];
        $sales = $system->chargesSales() ? strtr($this->template('sales.md'), $values) : '';

        return strtr($this->template('integration.md'), $values + ['{{SALES_SECTION}}' => $sales]);
    }

    private function template(string $name): string
    {
        return (string) file_get_contents(resource_path(self::TEMPLATES.$name));
    }
}
