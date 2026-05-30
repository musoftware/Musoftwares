<?php

namespace Modules\Booking\app\Features\BookingRules\Services\Resolvers;

use Modules\Booking\app\Features\BookingRules\Models\BookingAdvancedRule;
use Illuminate\Support\Collection;

class BookingRulesConflictResolver
{
    /**
     * Resolves conflicts between multiple matching rules before execution.
     * E.g., if one says "Approve" and another says "Reject".
     */
    public function resolve(Collection $matchingRules): Collection
    {
        // Sort by priority descending
        $sorted = $matchingRules->sortByDesc('priority');

        // Advanced conflict resolution logic would go here
        // For example, if a high priority rule issues a terminal action like 'reject',
        // we might drop lower priority rules.

        return $sorted;
    }
}
