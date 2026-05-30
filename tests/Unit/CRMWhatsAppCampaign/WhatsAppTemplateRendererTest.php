<?php

namespace Tests\Unit\CRMWhatsAppCampaign;

use Tests\TestCase;
use Modules\CRM\app\Features\CRMWhatsAppCampaigns\Services\WhatsAppTemplateRenderer;

class WhatsAppTemplateRendererTest extends TestCase
{
    protected WhatsAppTemplateRenderer $renderer;

    protected function setUp(): void { parent::setUp(); $this->renderer = new WhatsAppTemplateRenderer(); }

    public function test_renders_placeholders(): void
    {
        $body = 'Hello {{customer_name}}, welcome to {{company_name}}!';
        $data = ['customer_name' => 'Ahmed', 'company_name' => 'Acme'];
        $result = $this->renderer->render($body, $data);

        $this->assertEquals('Hello Ahmed, welcome to Acme!', $result);
    }

    public function test_uses_fallback_for_missing_values(): void
    {
        $body = 'Hello {{customer_name}}!';
        $result = $this->renderer->render($body, []);

        $this->assertEquals('Hello Customer!', $result);
    }

    public function test_handles_custom_data_fields(): void
    {
        $body = 'Order {{order_id}} for {{custom_field}}';
        $data = ['order_id' => 'ORD-123', 'custom_data' => ['custom_field' => 'Premium']];
        $result = $this->renderer->render($body, $data);

        $this->assertEquals('Order ORD-123 for Premium', $result);
    }

    public function test_extracts_placeholders(): void
    {
        $body = 'Hello {{customer_name}}, your order {{order_id}} is ready!';
        $placeholders = $this->renderer->extractPlaceholders($body);

        $this->assertContains('customer_name', $placeholders);
        $this->assertContains('order_id', $placeholders);
        $this->assertCount(2, $placeholders);
    }

    public function test_validates_unknown_placeholders(): void
    {
        $body = 'Hello {{unknown_field}}!';
        $errors = $this->renderer->validate($body);

        $this->assertNotEmpty($errors);
        $this->assertStringContains('unknown_field', $errors[0]);
    }

    public function test_preview_with_sample_data(): void
    {
        $body = 'Hi {{customer_name}}, call us at {{phone}}!';
        $result = $this->renderer->preview($body);

        $this->assertStringContainsString('Ahmed Mohamed', $result);
        $this->assertStringContainsString('+201234567890', $result);
    }

    public function test_returns_available_placeholders(): void
    {
        $available = $this->renderer->getAvailablePlaceholders();
        $this->assertContains('customer_name', $available);
        $this->assertContains('lead_name', $available);
        $this->assertContains('company_name', $available);
    }

    protected function assertStringContains(string $needle, string $haystack): void
    {
        $this->assertStringContainsString($needle, $haystack);
    }
}
