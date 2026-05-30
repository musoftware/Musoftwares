<?php

namespace Modules\CRM\Infrastructure\Events;

use Illuminate\Support\Facades\Event;
use Modules\CRM\Infrastructure\Context\TenantContext;

class TenantAwareEventDispatcher
{
    public function __construct(
        protected TenantContext $context
    ) {}

    /**
     * Dispatch an event, automatically injecting the current tenant and branch context
     * into the event object if it uses the TenantAwareEvent trait.
     */
    public function dispatch($event)
    {
        if (method_exists($event, 'setTenantContext')) {
            $event->setTenantContext($this->context->getTenantId(), $this->context->getBranchId());
        }

        return Event::dispatch($event);
    }
}
