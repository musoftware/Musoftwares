<?php

namespace Modules\Booking\tests\Unit\Booking\SmsNotifications;

use Tests\TestCase;
use Modules\Booking\app\Features\SmsNotifications\Services\SmsTemplateRenderer;
use Modules\Booking\Models\Booking;
use App\Models\User;
use Modules\Booking\Models\Service;
use Modules\Booking\Models\Resource;

class SmsTemplateRendererTest extends TestCase
{
    public function test_renders_template_placeholders_correctly()
    {
        $renderer = new SmsTemplateRenderer();

        $customer = User::factory()->make(['name' => 'John Doe']);
        $service = new Service(['name' => 'Dental Checkup']);
        $resource = new Resource(['name' => 'Dr. Smith']);

        $booking = new Booking();
        $booking->setRelation('customer', $customer);
        $booking->setRelation('service', $service);
        $booking->setRelation('resource', $resource);
        $booking->start_date = '2026-10-15';
        $booking->start_time = '14:30:00';

        $template = "Hi {{customer_name}}, your {{service_name}} with {{resource_name}} is confirmed for {{booking_date}} at {{booking_time}}.";
        
        $rendered = $renderer->render($template, $booking);

        $this->assertEquals("Hi John Doe, your Dental Checkup with Dr. Smith is confirmed for 2026-10-15 at 14:30.", $rendered);
    }
}
