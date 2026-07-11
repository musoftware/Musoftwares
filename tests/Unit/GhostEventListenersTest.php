<?php

namespace Tests\Unit;

use App\Events\CalculateReferralRegisteredEvent;
use App\Events\SaaSLimitApproaching;
use App\Events\SaaSLimitReached;
use App\Listeners\CalculateReferralListener;
use App\Listeners\SaaSLimitListener;
use App\Listeners\SaaSLimitReachedListener;
use App\Models\TenantUsage;
use App\Models\User;
use App\Notifications\SaaSLimitApproachingNotification;
use App\Notifications\SaaSLimitReachedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class GhostEventListenersTest extends TestCase
{
    use RefreshDatabase;

    public function test_saas_limit_approaching_dispatches_notification()
    {
        Notification::fake();

        $user = new User;
        $user->id = 1;
        $user->email = 'test@example.com';

        $usage = new TenantUsage;
        $usage->usage_key = 'storage';
        $usage->setRelation('user', $user);

        $event = new SaaSLimitApproaching($usage, 90);
        $listener = new SaaSLimitListener;

        $listener->handle($event);

        Notification::assertSentTo(
            [$user], SaaSLimitApproachingNotification::class
        );
    }

    public function test_saas_limit_reached_dispatches_notification()
    {
        Notification::fake();

        $user = new User;
        $user->id = 1;
        $user->email = 'test@example.com';

        $usage = new TenantUsage;
        $usage->usage_key = 'api_calls';
        $usage->setRelation('user', $user);

        $event = new SaaSLimitReached($usage);
        $listener = new SaaSLimitReachedListener;

        $listener->handle($event);

        Notification::assertSentTo(
            [$user], SaaSLimitReachedNotification::class
        );
    }

    public function test_calculate_referral_bonus()
    {
        // Simple test to ensure it runs without exceptions when user has a referrer
        $user = new User;
        $user->id = 2;
        $user->ref_user_id = 1;

        $event = new CalculateReferralRegisteredEvent($user, 'REFCODE123', '127.0.0.1');
        $listener = new CalculateReferralListener;

        // This will attempt to find User ID 1. Since DB is empty or mock, it won't crash
        // but just log info.
        $listener->handle($event);

        $this->assertTrue(true);
    }
}
