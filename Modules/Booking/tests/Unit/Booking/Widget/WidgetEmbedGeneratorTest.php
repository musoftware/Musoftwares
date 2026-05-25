<?php

namespace Modules\Booking\tests\Unit\Booking\Widget;

use Tests\TestCase;
use Modules\Booking\app\Features\Widget\Services\WidgetEmbedGenerator;
use Modules\Booking\app\Features\Widget\Models\BookingWidget;

class WidgetEmbedGeneratorTest extends TestCase
{
    public function test_generator_creates_valid_js_payload()
    {
        $generator = new WidgetEmbedGenerator();
        $widget = new BookingWidget();
        $widget->uuid = '1234-abcd';

        $js = $generator->generateJsPayload($widget);

        $this->assertStringContainsString('iframe.src = "', $js);
        $this->assertStringContainsString('/api/v1/public/widgets/1234-abcd/ui', $js);
        $this->assertStringContainsString('document.createElement("iframe")', $js);
    }
}
