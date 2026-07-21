<?php

namespace App\Providers;

use App\Events\AmountReceived;
use App\Events\CalculateReferralRegisteredEvent;
use App\Events\ContractSigned;
// Domain Events
use App\Events\InventoryAdjusted;
use App\Events\InvoiceCancelled;
use App\Events\InvoiceCreated;
use App\Events\InvoiceItemAdded;
use App\Events\InvoicePaid;
use App\Events\MarketplaceOrderCompleted;
use App\Events\MarketplaceOrderPlaced;
use App\Events\MessageSent;
use App\Events\ReferralCommissionEarned;
use App\Events\SaaSLimitApproaching;
use App\Events\SaaSLimitReached;
use App\Events\TimerUpdated;
use App\Events\WalletCredited;
use App\Events\WalletDebited;
use App\Events\WithdrawalApproved;
use App\Events\WithdrawalRequested;
use App\Listeners\ActivityEventListener;
use App\Listeners\AmountReceivedListener;
use App\Listeners\AutomationEngineListener;
use App\Listeners\CalculateReferralListener;
use App\Listeners\NotificationEventListener;
use App\Listeners\SaaSLimitListener;
use App\Listeners\SaaSLimitReachedListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

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
        InvoiceCreated::class => [ActivityEventListener::class, NotificationEventListener::class],
        InvoiceItemAdded::class => [NotificationEventListener::class],
        InvoiceCancelled::class => [NotificationEventListener::class],
        ContractSigned::class => [ActivityEventListener::class, NotificationEventListener::class],
        InventoryAdjusted::class => [ActivityEventListener::class],
        InvoicePaid::class => [ActivityEventListener::class, NotificationEventListener::class],
        WalletCredited::class => [ActivityEventListener::class],
        WalletDebited::class => [ActivityEventListener::class],
        WithdrawalRequested::class => [ActivityEventListener::class, NotificationEventListener::class],
        WithdrawalApproved::class => [ActivityEventListener::class, NotificationEventListener::class],
        ReferralCommissionEarned::class => [ActivityEventListener::class],
        AmountReceived::class => [AmountReceivedListener::class, NotificationEventListener::class],
        CalculateReferralRegisteredEvent::class => [CalculateReferralListener::class],
        SaaSLimitApproaching::class => [SaaSLimitListener::class],
        SaaSLimitReached::class => [SaaSLimitReachedListener::class],

        // Marketplace
        MarketplaceOrderPlaced::class => [
            ActivityEventListener::class,
            \App\Listeners\CreateProjectFromMarketplaceOrder::class,
        ],
        MarketplaceOrderCompleted::class => [ActivityEventListener::class],

        // Messaging & Timer
        MessageSent::class => [ActivityEventListener::class],
        TimerUpdated::class => [ActivityEventListener::class],

    ];

    public function boot(): void
    {
        parent::boot();

        Event::listen('*', [AutomationEngineListener::class, 'handle']);
    }
}
