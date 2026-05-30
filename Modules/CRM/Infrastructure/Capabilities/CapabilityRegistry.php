<?php

namespace Modules\CRM\Infrastructure\Capabilities;

use Exception;

class CapabilityRegistry
{
    /**
     * @var array<string, array{dependencies: string[], description: string}>
     */
    protected array $capabilities = [];

    /**
     * Register a capability and its required parent capabilities.
     * 
     * @param string $capability The name of the capability (e.g. 'crm-wa-inbox')
     * @param array $dependencies Array of capabilities it depends on (e.g. ['crm'])
     * @param string $description Optional description
     */
    public function register(string $capability, array $dependencies = [], string $description = ''): void
    {
        $this->capabilities[$capability] = [
            'dependencies' => $dependencies,
            'description'  => $description,
        ];
    }

    /**
     * Get all registered capabilities
     */
    public function getCapabilities(): array
    {
        return $this->capabilities;
    }

    /**
     * Given an array of directly subscribed features, return a filtered array
     * containing only the features whose complete dependency chain is also active.
     *
     * @param array $subscribedFeatures Array of feature keys the user has active subscriptions for
     * @return array Array of feature keys that are fully entitled (dependencies met)
     */
    public function resolveEntitlements(array $subscribedFeatures): array
    {
        $entitled = [];

        foreach ($subscribedFeatures as $feature) {
            if ($this->isEntitled($feature, $subscribedFeatures)) {
                $entitled[] = $feature;
            }
        }

        return $entitled;
    }

    /**
     * Recursively check if a feature and all its dependencies are present in the subscribed features.
     */
    protected function isEntitled(string $feature, array $subscribedFeatures, array $visited = []): bool
    {
        // Cycle detection to prevent infinite loops in bad DAG configs
        if (in_array($feature, $visited)) {
            throw new Exception("Circular dependency detected for capability: {$feature}");
        }

        // If the user doesn't even have a subscription for this specific feature, it's not entitled
        if (!in_array($feature, $subscribedFeatures)) {
            return false;
        }

        // If the feature is not registered, we'll assume it has no dependencies 
        // (legacy fallback, though strict mode would reject it)
        if (!isset($this->capabilities[$feature])) {
            return true;
        }

        $visited[] = $feature;

        // Check all dependencies
        foreach ($this->capabilities[$feature]['dependencies'] as $dependency) {
            if (!$this->isEntitled($dependency, $subscribedFeatures, $visited)) {
                return false;
            }
        }

        return true;
    }
}
