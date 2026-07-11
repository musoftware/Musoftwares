<?php

namespace Tests\Feature\Booking\WhiteLabel;

use Modules\Booking\app\Features\WhiteLabel\Models\WhiteLabelSetting;
use Modules\Booking\app\Features\WhiteLabel\Services\WhiteLabelThemeManager;
use Tests\TestCase;

class WhiteLabelThemeManagerTest extends TestCase
{
    public function test_it_generates_css_variables()
    {
        $manager = app(WhiteLabelThemeManager::class);
        $setting = new WhiteLabelSetting([
            'primary_color' => '#123456',
            'secondary_color' => '#abcdef',
            'font_family' => 'Roboto',
            'is_active' => true,
        ]);

        $css = $manager->generateCssVariables($setting);

        $this->assertStringContainsString('--booking-primary-color: #123456;', $css);
        $this->assertStringContainsString('--booking-secondary-color: #abcdef;', $css);
        $this->assertStringContainsString("--booking-font-family: 'Roboto';", $css);
    }
}
