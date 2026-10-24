<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\DailyNewServicesDigestNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Kreait\Firebase\Messaging\CloudMessage;
use Tests\TestCase;

/** Two bugs that made queued jobs fail in production (254 digest mails, 102 push messages). */
class NotificationRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_daily_digest_mail_builds_in_english_without_calling_a_missing_method(): void
    {
        $notification = new DailyNewServicesDigestNotification(new Collection, new Collection);
        $mail = $notification->toMail(User::factory()->create());

        $this->assertSame('en', $notification->locale);
        $this->assertNotEmpty($mail->subject);
    }

    public function test_push_messages_use_the_token_method_that_exists_in_the_installed_firebase_package(): void
    {
        $this->assertTrue(method_exists(CloudMessage::class, 'withToken'));
        $this->assertFalse(method_exists(CloudMessage::class, 'withTarget'));

        $message = CloudMessage::fromArray(['notification' => ['title' => 'Hi']])->withToken('device-token');
        $this->assertSame('device-token', $message->jsonSerialize()['token']);

        foreach (['app/Jobs/SendFcmNotificationJob.php', 'app/Jobs/SendCampaignBroadcastJob.php'] as $file) {
            $this->assertStringNotContainsString('withTarget(', file_get_contents(base_path($file)), $file);
        }
    }
}
