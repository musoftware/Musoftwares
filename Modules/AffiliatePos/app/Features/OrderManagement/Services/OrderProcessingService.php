<?php

namespace Modules\AffiliatePos\app\Features\OrderManagement\Services;

use Modules\AffiliatePos\Models\Order;
use Modules\AffiliatePos\Models\OrderItem;
use Modules\AffiliatePos\Models\Transaction;
use Illuminate\Support\Facades\DB;

class OrderProcessingService
{
    public function changeStatus(Order $order, string $newStatus)
    {
        return DB::transaction(function () use ($order, $newStatus) {
            $oldStatus = $order->status;
            $order->update(['status' => $newStatus]);
            
            $order->items()->update(['status' => $newStatus]);

            if ($newStatus === 'cancelled' && $oldStatus !== 'cancelled') {
                $this->releaseStock($order);
            }

            if ($newStatus === 'delivered' && $oldStatus !== 'delivered') {
                $this->processCommissions($order);
            }
            
            if ($newStatus === 'returned' && $oldStatus !== 'returned') {
                $this->reverseCommissions($order);
                $this->releaseStock($order);
            }
            
            return $order;
        });
    }

    public function partialDelivery(Order $order, array $itemsStatus)
    {
        return DB::transaction(function () use ($order, $itemsStatus) {
            $deliveredCount = 0;
            $totalItemsCount = count($itemsStatus);

            foreach ($itemsStatus as $itemId => $status) {
                $item = $order->items()->find($itemId);
                if ($item) {
                    $item->update(['status' => $status]);
                    
                    if ($status === 'delivered') {
                        $deliveredCount++;
                        $this->processItemCommission($order, $item);
                    } elseif ($status === 'returned' || $status === 'cancelled') {
                        $this->releaseItemStock($order, $item);
                        $this->reverseItemCommission($order, $item);
                    }
                }
            }

            if ($deliveredCount === $totalItemsCount) {
                $order->update(['status' => 'delivered']);
            } elseif ($deliveredCount > 0 && $deliveredCount < $totalItemsCount) {
                $order->update(['status' => 'partial_delivery']);
            }

            return $order;
        });
    }
    
    public function bulkChangeStatus(array $orderIds, string $newStatus)
    {
        return DB::transaction(function () use ($orderIds, $newStatus) {
            foreach ($orderIds as $id) {
                $order = Order::find($id);
                if ($order) {
                    $this->changeStatus($order, $newStatus);
                }
            }
        });
    }

    public function bulkAssignShippingCompany(array $orderIds, $shippingCompanyId, $shippingCompanyName)
    {
        return Order::whereIn('id', $orderIds)->update([
            'shipping_company_id' => $shippingCompanyId,
        ]);
    }

    protected function processItemCommission(Order $order, OrderItem $item)
    {
        if ($item->user_id && $item->total_commission > 0 && !$item->generated_transaction) {
            Transaction::create([
                'tenant_id' => $order->tenant_id,
                'user_id' => $item->user_id,
                'relation_type' => get_class($item),
                'relation_id' => $item->id,
                'type' => 'commission',
                'amount' => $item->total_commission,
                'notes' => "Commission for partial order {$order->unique_id}"
            ]);
            $item->update(['generated_transaction' => 1]);
        }
    }
    
    protected function reverseItemCommission(Order $order, OrderItem $item)
    {
        if ($item->user_id && $item->generated_transaction) {
            Transaction::create([
                'tenant_id' => $order->tenant_id,
                'user_id' => $item->user_id,
                'relation_type' => get_class($item),
                'relation_id' => $item->id,
                'type' => 'commission_reversal',
                'amount' => -$item->total_commission,
                'notes' => "Commission reversed for partial order {$order->unique_id}"
            ]);
            $item->update(['generated_transaction' => 0]);
        }
    }

    protected function releaseItemStock(Order $order, OrderItem $item)
    {
        if ($item->sku) {
            $item->sku->increaseStock($item->qty, [
                'reference_type' => Order::class,
                'reference_id' => $order->id,
                'description' => "Stock released for item in order {$order->unique_id}"
            ]);
        }
    }

    protected function releaseStock(Order $order)
    {
        foreach ($order->items as $item) {
            $this->releaseItemStock($order, $item);
        }
    }

    protected function processCommissions(Order $order)
    {
        foreach ($order->items as $item) {
            $this->processItemCommission($order, $item);
        }
    }

    protected function reverseCommissions(Order $order)
    {
        foreach ($order->items as $item) {
            $this->reverseItemCommission($order, $item);
        }
    }
}
