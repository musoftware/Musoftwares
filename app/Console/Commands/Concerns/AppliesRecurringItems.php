<?php

namespace App\Console\Commands\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Shared loop for the add:recurring_* commands.
 * Walks active items in chunks and isolates failures, so one broken item
 * (for example a duplicate recurring record) never stops the other items.
 */
trait AppliesRecurringItems
{
    private const RECURRING_CHUNK_SIZE = 200;

    /**
     * @return int Number of items that failed to apply.
     */
    protected function applyRecurringItems(Builder $activeItems, string $label): int
    {
        $failed = 0;

        $activeItems->chunkById(self::RECURRING_CHUNK_SIZE, function ($items) use ($label, &$failed) {
            foreach ($items as $item) {
                $failed += $this->applyRecurringItem($item, $label) ? 0 : 1;
            }
        });

        return $failed;
    }

    private function applyRecurringItem(Model $item, string $label): bool
    {
        try {
            $item->apply();

            return true;
        } catch (Throwable $e) {
            Log::error("{$label}: failed to apply recurring item", [
                'model' => $item::class,
                'id' => $item->getKey(),
                'exception' => $e::class,
                'error' => $e->getMessage(),
            ]);
            $this->error("{$label}: item #{$item->getKey()} failed: {$e->getMessage()}");

            return false;
        }
    }
}
