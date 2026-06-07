<?php

namespace Modules\Booking\tests\Unit\Booking\SmsNotifications;

use Tests\TestCase;
use Modules\Booking\app\Features\SmsNotifications\Services\SmsTemplateRenderer;
use App\Models\User;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\BookingEventType;

class SmsTemplateRendererTest extends TestCase
{
    public function test_renders_template_placeholders_correctly()
    {
        $renderer = new SmsTemplateRenderer();

        $customer = User::factory()->make(['name' => 'John Doe']);
        
        $eventType = new BookingEventType();
        $eventType->title = 'Dental Checkup';
        
        $provider = User::factory()->make(['name' => 'Dr. Smith']);

        $booking = new Booking();
        $booking->setRelation('clientUser', $customer);
        $booking->setRelation('eventType', $eventType);
        $booking->setRelation('provider', $provider);
        $booking->starts_at = \Carbon\Carbon::parse('2026-06-15 14:30:00');

        $template = "Hi {{customer_name}}, your {{service_name}} with {{resource_name}} is confirmed for {{booking_date}} at {{booking_time}}.";
        
        $rendered = $renderer->render($template, $booking);

        $this->assertEquals("Hi John Doe, your Dental Checkup with Dr. Smith is confirmed for 2026-06-15 at 14:30.", $rendered);
    }
}
