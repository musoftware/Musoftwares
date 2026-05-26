<?php

namespace Modules\AffiliatePos\Traits;

use Modules\AffiliatePos\Models\StockMutation;

trait HasStock
{
    public function stockMutations()
    {
        return $this->morphMany(StockMutation::class, 'stockable');
    }

    public function stock()
    {
        return $this->stockMutations()->sum('amount');
    }

    public function setStock($amount, $arguments = [])
    {
        $currentStock = $this->stock();
        $mutation = $amount - $currentStock;
        if ($mutation != 0) {
            $this->createStockMutation($mutation, $arguments);
        }
    }

    public function increaseStock($amount, $arguments = [])
    {
        $this->createStockMutation($amount, $arguments);
    }

    public function decreaseStock($amount, $arguments = [])
    {
        $this->createStockMutation(-$amount, $arguments);
    }

    protected function createStockMutation($amount, $arguments = [])
    {
        $mutation = new StockMutation();
        $mutation->amount = $amount;
        $mutation->reference_type = $arguments['reference_type'] ?? null;
        $mutation->reference_id = $arguments['reference_id'] ?? null;
        if (isset($arguments['reference']) && is_object($arguments['reference'])) {
            $mutation->reference_type = get_class($arguments['reference']);
            $mutation->reference_id = $arguments['reference']->getKey();
        }
        $mutation->description = $arguments['description'] ?? null;
        
        $this->stockMutations()->save($mutation);
    }
}
