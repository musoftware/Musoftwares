<?php

namespace Modules\Booking\tests\Unit\Booking\SmsNotifications;

use Tests\TestCase;
use Modules\Booking\app\Features\SmsNotifications\Services\Providers\SmsProviderManager;
use Modules\Booking\app\Features\SmsNotifications\Services\Providers\SmsMisrProvider;
use Modules\Booking\app\Features\SmsNotifications\Services\Providers\TwilioProvider;

class SmsProviderManagerTest extends TestCase
{
    public function test_manager_resolves_correct_provider()
    {
        $manager = new SmsProviderManager();

        $this->assertInstanceOf(SmsMisrProvider::class, $manager->resolve('smsmisr'));
        $this->assertInstanceOf(TwilioProvider::class, $manager->resolve('twilio'));
    }

    public function test_manager_throws_exception_on_invalid_provider()
    {
        $manager = new SmsProviderManager();

        $this->expectException(\InvalidArgumentException::class);
        $manager->resolve('fake_provider');
    }
}
