<?php

namespace Modules\Booking\app\Features\WhiteLabel\Services;

class WhiteLabelBrandingResolver
{
    private BookingWhiteLabelService $settingsService;
    private WhiteLabelThemeManager $themeManager;

    public function __construct(BookingWhiteLabelService $settingsService, WhiteLabelThemeManager $themeManager)
    {
        $this->settingsService = $settingsService;
        $this->themeManager = $themeManager;
    }

    /**
     * Build the unified branding array to be shared via Inertia or injected into views.
     */
    public function resolve(int $tenantId): array
    {
        $settings = $this->settingsService->getSettings($tenantId);

        if (!$settings->is_active) {
            return [
                'is_active' => false,
                'css_variables' => '',
            ];
        }

        return [
            'is_active' => true,
            'primary_color' => $settings->primary_color,
            'secondary_color' => $settings->secondary_color,
            'font_family' => $settings->font_family,
            'css_variables' => $this->themeManager->generateCssVariables($settings),
        ];
    }
}
