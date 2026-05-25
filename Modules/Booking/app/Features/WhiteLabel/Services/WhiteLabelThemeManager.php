<?php

namespace Modules\Booking\app\Features\WhiteLabel\Services;

use Modules\Booking\app\Features\WhiteLabel\Models\WhiteLabelTheme;
use Modules\Booking\app\Features\WhiteLabel\Models\WhiteLabelSetting;

class WhiteLabelThemeManager
{
    /**
     * Generates a dynamic CSS payload based on tenant settings.
     */
    public function generateCssVariables(WhiteLabelSetting $settings): string
    {
        if (!$settings->is_active) {
            return '';
        }

        $primary = $settings->primary_color ?? '#000000';
        $secondary = $settings->secondary_color ?? '#ffffff';
        $font = $settings->font_family ?? 'Inter, sans-serif';

        return "
            :root {
                --booking-primary-color: {$primary};
                --booking-secondary-color: {$secondary};
                --booking-font-family: '{$font}';
            }
            {$settings->custom_css}
        ";
    }

    /**
     * Create a new tenant-specific theme preset.
     */
    public function createThemePreset(int $tenantId, string $name, array $settingsJson): WhiteLabelTheme
    {
        return WhiteLabelTheme::create([
            'tenant_id' => $tenantId,
            'name' => $name,
            'settings_json' => $settingsJson,
            'is_default' => false,
        ]);
    }
}
