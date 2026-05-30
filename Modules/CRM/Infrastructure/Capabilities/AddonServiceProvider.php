<?php

namespace Modules\CRM\Infrastructure\Capabilities;

use Illuminate\Support\ServiceProvider;
use Modules\CRM\Infrastructure\Capabilities\CapabilityRegistry;

abstract class AddonServiceProvider extends ServiceProvider
{
    /**
     * The unique identifier for this capability/addon (e.g., 'crm-wa-inbox')
     */
    abstract protected function getCapabilityIdentifier(): string;

    /**
     * Array of capability identifiers this addon depends on.
     */
    protected function getDependencies(): array
    {
        return [];
    }

    /**
     * Brief description of the capability.
     */
    protected function getDescription(): string
    {
        return '';
    }

    /**
     * Boot the addon. It will register its capability to the registry.
     */
    public function boot(): void
    {
        // Register this addon in the global capability registry
        $registry = $this->app->make(CapabilityRegistry::class);
        
        $registry->register(
            $this->getCapabilityIdentifier(),
            $this->getDependencies(),
            $this->getDescription()
        );

        // Perform standard boot logic
        $this->bootAddon();
    }

    /**
     * Addons should override this method instead of standard boot()
     */
    protected function bootAddon(): void
    {
        //
    }
}
