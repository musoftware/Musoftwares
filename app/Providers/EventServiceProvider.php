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
use App\Events\AmountReceived;
use App\Events\CalculateReferralRegisteredEvent;
use App\Events\SaaSLimitApproaching;
use App\Events\SaaSLimitReached;
use App\Events\InvoiceCreated;
use App\Events\ContractSigned;
use App\Events\InventoryAdjusted;
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
        InvoicePaid::class             => [ActivityEventListener::class, NotificationEventListener::class],
        WalletCredited::class          => [ActivityEventListener::class],
        WalletDebited::class           => [ActivityEventListener::class],
        WithdrawalRequested::class     => [ActivityEventListener::class],
        WithdrawalApproved::class      => [ActivityEventListener::class, NotificationEventListener::class],
        ReferralCommissionEarned::class => [ActivityEventListener::class],
        AmountReceived::class          => [AmountReceivedListener::class],
        CalculateReferralRegisteredEvent::class => [CalculateReferralListener::class],
        SaaSLimitApproaching::class    => [SaaSLimitListener::class],
        SaaSLimitReached::class        => [SaaSLimitReachedListener::class],



        // Marketplace
        MarketplaceOrderPlaced::class  => [ActivityEventListener::class],
        MarketplaceOrderCompleted::class => [ActivityEventListener::class],

        // Freelance
        ProposalAccepted::class        => [ActivityEventListener::class],

        // Messaging & Timer
        MessageSent::class             => [ActivityEventListener::class],
        TimerUpdated::class            => [ActivityEventListener::class],


    ];

    public function boot(): void
    {
        parent::boot();

        \Illuminate\Support\Facades\Event::listen('*', [\App\Listeners\AutomationEngineListener::class, 'handle']);
    }
}
