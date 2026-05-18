<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Listeners\ActivityEventListener;

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
        InvoicePaid::class             => [ActivityEventListener::class],
        WalletCredited::class          => [ActivityEventListener::class],
        WalletDebited::class           => [ActivityEventListener::class],
        WithdrawalRequested::class     => [ActivityEventListener::class],
        WithdrawalApproved::class      => [ActivityEventListener::class],
        ReferralCommissionEarned::class => [ActivityEventListener::class],

        // Marketplace
        MarketplaceOrderPlaced::class  => [ActivityEventListener::class],
        MarketplaceOrderCompleted::class => [ActivityEventListener::class],

        // Freelance
        ProposalAccepted::class        => [ActivityEventListener::class],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
