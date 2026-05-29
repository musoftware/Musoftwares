<?php

namespace Modules\ERP\Services;

use Modules\ERP\Models\Product;
use Modules\ERP\Models\ProductStockLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class InventoryService
{
    public function createProduct(array $validated, int $tenantId): Product
    {
        if (isset($validated['sku']) && Product::where('tenant_id', $tenantId)->where('sku', $validated['sku'])->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'sku' => __('erp.sku_already_exists')
            ]);
        }

        return DB::transaction(function () use ($validated, $tenantId) {
            $product = Product::create(array_merge($validated, ['tenant_id' => $tenantId]));

            if ($product->stock_quantity > 0) {
                ProductStockLog::create([
                    'product_id' => $product->id,
                    'tenant_id' => $tenantId,
                    'user_id' => Auth::id(),
                    'change_amount' => $product->stock_quantity,
                    'new_quantity' => $product->stock_quantity,
                    'reason' => __('erp.initial_stock'),
                ]);
            }

            return $product;
        });
    }

    public function updateProduct(Product $product, array $validated, int $tenantId): Product
    {
        if (isset($validated['sku']) && Product::where('tenant_id', $tenantId)->where('sku', $validated['sku'])->where('id', '!=', $product->id)->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'sku' => __('erp.sku_already_exists')
            ]);
        }

        unset($validated['stock_quantity']); // Enforce stock updates via adjustment

        $product->update($validated);

        return $product;
    }

    public function deleteProduct(Product $product): void
    {
        $product->delete();
    }

    public function adjustStock(Product $product, float $changeAmount, string $reason, int $tenantId): Product
    {
        return DB::transaction(function () use ($product, $changeAmount, $reason, $tenantId) {
            $oldQuantity = (float) $product->stock_quantity;
            $newQuantity = $oldQuantity + $changeAmount;

            $product->update(['stock_quantity' => $newQuantity]);

            ProductStockLog::create([
                'product_id' => $product->id,
                'tenant_id' => $tenantId,
                'user_id' => Auth::id(),
                'change_amount' => $changeAmount,
                'new_quantity' => $newQuantity,
                'reason' => $reason,
            ]);

            $product->checkLowStock($oldQuantity);

            return $product;
        });
    }
}
