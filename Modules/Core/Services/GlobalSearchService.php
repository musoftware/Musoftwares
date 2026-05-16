<?php

namespace Modules\Core\Services;

use App\Models\User;
use Modules\ERP\Models\Invoice;
use Modules\Marketplace\Models\Service;
use Modules\Freelance\Models\Job;
use Modules\ERP\Models\WithdrawalRequest;
use Illuminate\Support\Facades\Cache;

class GlobalSearchService
{
    /**
     * Perform a global search across multiple searchable models.
     */
    public function search(string $query, ?string $moduleScope = null): array
    {
        $results = [];

        if (!$query) {
            return $results;
        }

        $this->cacheRecentSearch($query);

        $models = [
            'users' => User::class,
            'invoices' => Invoice::class,
            'marketplace_services' => Service::class,
            'freelance_jobs' => Job::class,
            'withdrawal_requests' => WithdrawalRequest::class,
        ];

        if ($moduleScope && isset($models[$moduleScope])) {
            $models = [$moduleScope => $models[$moduleScope]];
        }

        foreach ($models as $type => $modelClass) {
            if (class_exists($modelClass)) {
                // Laravel Scout's search with Meilisearch engine can handle fuzzy search inherently.
                // We're delegating fuzzy matching to Scout/Meilisearch.
                $results[$type] = $modelClass::search($query)
                    ->take(10) // Limit per type for global search
                    ->get();
            }
        }

        return $this->applyWeightedRanking($results, $query);
    }

    /**
     * Cache recent searches for the user.
     */
    protected function cacheRecentSearch(string $query): void
    {
        $userId = auth()->id();
        if (!$userId) return;

        $cacheKey = "recent_searches_{$userId}";
        $searches = Cache::get($cacheKey, []);

        // Add to front, remove duplicates, keep top 10
        array_unshift($searches, $query);
        $searches = array_slice(array_unique($searches), 0, 10);

        Cache::put($cacheKey, $searches, now()->addDays(7));
    }

    public function getRecentSearches(): array
    {
        $userId = auth()->id();
        if (!$userId) return [];

        return Cache::get("recent_searches_{$userId}", []);
    }

    /**
     * Apply weighted ranking if necessary.
     * Meilisearch handles primary ranking, but we can adjust logic here if we need
     * cross-index weighting in PHP.
     */
    protected function applyWeightedRanking(array $results, string $query): array
    {
        // Placeholder for cross-index weighting logic.
        // e.g., prioritize Exact Match in Title over Description across all results.
        return $results;
    }
}
