<?php

namespace App\Modules\CRMWhatsAppCampaigns\Services;

class WhatsAppTemplateRenderer
{
    /**
     * Default placeholder definitions with fallbacks.
     */
    protected array $systemPlaceholders = [
        'customer_name' => 'Customer',
        'lead_name'     => 'there',
        'company_name'  => '',
        'phone'         => '',
        'email'         => '',
        'order_id'      => '',
        'booking_link'  => '',
        'source'        => '',
        'status'        => '',
    ];

    /**
     * Render a template body with merge data.
     */
    public function render(string $body, array $mergeData = []): string
    {
        // Merge system defaults with provided data
        $data = array_merge($this->systemPlaceholders, $mergeData);

        // Handle custom_data nested access
        $customData = $data['custom_data'] ?? [];
        unset($data['custom_data']);

        // Replace standard placeholders
        $rendered = preg_replace_callback('/\{\{(\w+)\}\}/', function ($matches) use ($data, $customData) {
            $key = $matches[1];

            // Check direct merge data first
            if (isset($data[$key]) && $data[$key] !== '') {
                return $data[$key];
            }

            // Check custom_data
            if (isset($customData[$key])) {
                return $customData[$key];
            }

            // Return fallback from system defaults
            return $this->systemPlaceholders[$key] ?? $matches[0];
        }, $body);

        return $rendered;
    }

    /**
     * Validate a template body — check for unresolvable placeholders.
     */
    public function validate(string $body, array $availableKeys = []): array
    {
        $errors = [];

        preg_match_all('/\{\{(\w+)\}\}/', $body, $matches);
        $usedKeys = array_unique($matches[1] ?? []);

        $knownKeys = array_merge(
            array_keys($this->systemPlaceholders),
            $availableKeys
        );

        foreach ($usedKeys as $key) {
            if (!in_array($key, $knownKeys)) {
                $errors[] = "Unknown placeholder: {{{$key}}}";
            }
        }

        return $errors;
    }

    /**
     * Extract all placeholders from a template body.
     */
    public function extractPlaceholders(string $body): array
    {
        preg_match_all('/\{\{(\w+)\}\}/', $body, $matches);
        return array_unique($matches[1] ?? []);
    }

    /**
     * Preview a rendered template with sample data.
     */
    public function preview(string $body, ?array $sampleData = null): string
    {
        $sample = $sampleData ?? [
            'customer_name' => 'Ahmed Mohamed',
            'lead_name'     => 'Ahmed Mohamed',
            'company_name'  => 'Acme Corp',
            'phone'         => '+201234567890',
            'email'         => 'ahmed@example.com',
            'order_id'      => 'ORD-12345',
            'booking_link'  => 'https://app.example.com/book/abc123',
        ];

        return $this->render($body, $sample);
    }

    /**
     * Get all available system placeholders.
     */
    public function getAvailablePlaceholders(): array
    {
        return array_keys($this->systemPlaceholders);
    }
}
