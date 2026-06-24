<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Listeners\ActivityEventListener;
use App\Listeners\NotificationEventListener;

// Domain Events
use App\Events\InvoicePaid;
use App\Events\WalletCredited;
use App\Events\WalletDebited;
use App\Events\WithdrawalRequested;
use App\Events\WithdrawalApproved;
use App\Events\MarketplaceOrderPlaced;
use App\Events\MarketplaceOrderCompleted;
use App\Events\ProposalAccepted;
use App\Events\ReferralCommissionEarned;
use App\Events\MessageSent;
use App\Events\TimerUpdated;
use Modules\Booking\Events\BookingStatusChanged;
use Modules\Booking\Listeners\SendBookingNotification;
use App\Events\AmountReceived;
use App\Events\CalculateReferralRegisteredEvent;
use App\Events\SaaSLimitApproaching;
use App\Events\SaaSLimitReached;
use App\Events\InvoiceCreated;
use App\Events\ContractSigned;
use App\Events\InventoryAdjusted;
use Modules\ERP\Listeners\AccountingListener;
use App\Listeners\AmountReceivedListener;
use App\Listeners\CalculateReferralListener;
use App\Listeners\SaaSLimitListener;
use App\Listeners\SaaSLimitReachedListener;

/**
 * EventServiceProvider — wires all domain events to the Activity Engine listener.
 *
 * As the iSAAS ecosystem grows, add new events here.
 * The ActivityEventListener handles the mapping to ActivityService::log().
 */
class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        // ERP / Financial
        InvoiceCreated::class          => [ActivityEventListener::class],
        ContractSigned::class          => [ActivityEventListener::class],
        InventoryAdjusted::class       => [ActivityEventListener::class],
        InvoicePaid::class             => [ActivityEventListener::class, NotificationEventListener::class, AccountingListener::class],
        WalletCredited::class          => [ActivityEventListener::class, AccountingListener::class],
        WalletDebited::class           => [ActivityEventListener::class, AccountingListener::class],
        WithdrawalRequested::class     => [ActivityEventListener::class],
        WithdrawalApproved::class      => [ActivityEventListener::class, NotificationEventListener::class],
        ReferralCommissionEarned::class => [ActivityEventListener::class],
        AmountReceived::class          => [AmountReceivedListener::class],
        CalculateReferralRegisteredEvent::class => [CalculateReferralListener::class],
        SaaSLimitApproaching::class    => [SaaSLimitListener::class],
        SaaSLimitReached::class        => [SaaSLimitReachedListener::class],

        // Booking Custom Domain Events
        \Modules\Booking\app\Features\CustomDomains\Events\BookingCustomDomainCreated::class => [ActivityEventListener::class],
        \Modules\Booking\app\Features\CustomDomains\Events\BookingCustomDomainDeleted::class => [ActivityEventListener::class],
        \Modules\Booking\app\Features\CustomDomains\Events\BookingCustomDomainFailed::class => [ActivityEventListener::class],
        \Modules\Booking\app\Features\CustomDomains\Events\BookingCustomDomainPrimaryChanged::class => [ActivityEventListener::class],
        \Modules\Booking\app\Features\CustomDomains\Events\BookingCustomDomainVerified::class => [ActivityEventListener::class],
        \Modules\Booking\app\Features\PublicBooking\Events\BookingPagePublished::class => [ActivityEventListener::class],
        \Modules\Booking\app\Features\PublicBooking\Events\BookingPageSettingsUpdated::class => [ActivityEventListener::class],

        // Marketplace
        MarketplaceOrderPlaced::class  => [ActivityEventListener::class],
        MarketplaceOrderCompleted::class => [ActivityEventListener::class],

        // Freelance
        ProposalAccepted::class        => [ActivityEventListener::class],

        // Messaging & Timer
        MessageSent::class             => [ActivityEventListener::class],
        TimerUpdated::class            => [ActivityEventListener::class],

        // Booking
        BookingStatusChanged::class    => [
            SendBookingNotification::class,
            \Modules\Booking\app\Features\Analytics\Listeners\UpdateDailyMetricsListener::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();

        \Illuminate\Support\Facades\Event::listen('*', [\App\Listeners\AutomationEngineListener::class, 'handle']);
    }
}
