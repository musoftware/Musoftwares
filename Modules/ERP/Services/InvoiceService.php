<?php

namespace Modules\ERP\Services;

use Modules\ERP\Models\Invoice;
use Modules\ERP\Models\TenantClient;
use Modules\ERP\Models\Tenant;
use Modules\ERP\Models\Product;
use Modules\ERP\Models\ProductStockLog;
use App\Models\Currency;
use App\Services\ExchangeRateService;
use App\Services\ActivityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class InvoiceService
{
    protected $exchangeRateService;

    public function __construct(ExchangeRateService $exchangeRateService)
    {
        $this->exchangeRateService = $exchangeRateService;
    }

    public function createInvoice(array $validated, Tenant $tenant): Invoice
    {
        $client = TenantClient::with('currency')->where('tenant_id', $tenant->id)->findOrFail($validated['client_id']);
        $amountCurrency = $client->currency ? $client->currency->currency : 'USD';

        return DB::transaction(function () use ($validated, $tenant, $client, $amountCurrency) {
            $currency = Currency::find($tenant->base_currency_id);
            $businessCurrency = $currency ? $currency->currency : 'USD';
            $rate = $this->exchangeRateService->getRate($amountCurrency, $businessCurrency, $validated['issued_at']);

            $subtotal = collect($validated['items'])->sum(fn($i) => $i['unit_price'] * $i['quantity']);
            $discount = $validated['discount_amount'] ?? 0;
            $taxable  = $subtotal - $discount;
            $tax      = $taxable * (($validated['tax_rate'] ?? 0) / 100);
            $total    = $taxable + $tax;

            $invoice = Invoice::create([
                'tenant_id'         => $tenant->id,
                'client_id'         => $client->id,
                'project_id'        => $validated['project_id'] ?? null,
                'invoice_number'    => $validated['invoice_number'],
                'status'            => 'draft',
                'paid_amount'       => 0,
                'amount'            => $total,
                'currency_id'       => $client->currency_id,
                'business_amount'   => $total * $rate,
                'exchange_rate'     => $rate,
                'exchange_rate_date'=> $validated['issued_at'],
                'discount_amount'   => $discount,
                'tax_rate'          => $validated['tax_rate'] ?? 0,
                'tax_amount'        => $tax,
                'issued_at'         => $validated['issued_at'],
                'due_date'          => $validated['due_date'],
                'notes'             => $validated['notes'],
                'created_by'        => Auth::id(),
            ]);

            foreach ($validated['items'] as $index => $itemData) {
                $invoice->items()->create([
                    'tenant_id' => $invoice->tenant_id,
                    'type' => $itemData['type'],
                    'title' => $itemData['title'],
                    'description' => $itemData['description'] ?? null,
                    'unit_price' => $itemData['unit_price'],
                    'quantity' => $itemData['quantity'],
                    'total' => $itemData['unit_price'] * $itemData['quantity'],
                    'sort_order' => $index,
                    'product_id' => $itemData['product_id'] ?? null,
                ]);

                // Deduct stock if product is linked
                if (!empty($itemData['product_id'])) {
                    $product = Product::find($itemData['product_id']);
                    if ($product) {
                        $oldQuantity = (float) $product->stock_quantity;
                        $product->stock_quantity -= $itemData['quantity'];
                        $product->save();

                        ProductStockLog::create([
                            'tenant_id' => $tenant->id,
                            'product_id' => $product->id,
                            'change_amount' => -$itemData['quantity'],
                            'new_quantity' => $product->stock_quantity,
                            'reason' => "Added to Invoice #{$invoice->invoice_number}",
                        ]);

                        $product->checkLowStock($oldQuantity);
                    }
                }
            }

            if (!empty($validated['costs'])) {
                foreach ($validated['costs'] as $costData) {
                    $costRate = $this->exchangeRateService->getRate($amountCurrency, $businessCurrency, $validated['issued_at']);
                    $invoice->costs()->create([
                        'tenant_id' => $invoice->tenant_id,
                        'title' => $costData['title'],
                        'amount' => $costData['amount'],
                        'currency_id' => $client->currency_id,
                        'business_amount' => $costData['amount'] * $costRate,
                        'business_currency_id' => $tenant->base_currency_id,
                        'exchange_rate' => $costRate,
                        'exchange_rate_date' => $validated['issued_at'],
                        'payment_status' => 'unpaid',
                    ]);
                }
            }

            ActivityService::log(
                event: 'invoice.created',
                description: "Created invoice #{$invoice->invoice_number}",
                subject: $invoice,
                workspace: 'erp'
            );

            return $invoice;
        });
    }

    public function updateInvoice(Invoice $invoice, array $validated, Tenant $tenant): Invoice
    {
        $client = TenantClient::with('currency')->where('tenant_id', $tenant->id)->findOrFail($validated['client_id']);
        $amountCurrency = $client->currency ? $client->currency->currency : 'USD';

        return DB::transaction(function () use ($invoice, $validated, $tenant, $client, $amountCurrency) {
            $currency = Currency::find($tenant->base_currency_id);
            $businessCurrency = $currency ? $currency->currency : 'USD';
            $rate = $this->exchangeRateService->getRate($amountCurrency, $businessCurrency, $validated['issued_at']);

            $subtotal = collect($validated['items'])->sum(fn($i) => $i['unit_price'] * $i['quantity']);
            $discount = $validated['discount_amount'] ?? 0;
            $taxable = $subtotal - $discount;
            $tax = $taxable * (($validated['tax_rate'] ?? 0) / 100);
            $total = $taxable + $tax;

            $invoice->update([
                'client_id' => $validated['client_id'],
                'project_id' => $validated['project_id'] ?? null,
                'amount' => $total,
                'currency_id' => $client->currency_id,
                'business_amount' => $total * $rate,
                'exchange_rate' => $rate,
                'exchange_rate_date' => $validated['issued_at'],
                'discount_amount' => $discount,
                'tax_rate' => $validated['tax_rate'] ?? 0,
                'tax_amount' => $tax,
                'issued_at' => $validated['issued_at'],
                'due_date' => $validated['due_date'],
                'notes' => $validated['notes'],
            ]);

            $itemIds = collect($validated['items'])->pluck('id')->filter()->toArray();
            
            // Restore stock for deleted items
            $deletedItems = $invoice->items()->whereNotIn('id', $itemIds)->get();
            foreach ($deletedItems as $delItem) {
                if ($delItem->product_id) {
                    $product = Product::find($delItem->product_id);
                    if ($product) {
                        $product->stock_quantity += $delItem->quantity;
                        $product->save();
                        ProductStockLog::create([
                            'tenant_id' => $tenant->id,
                            'product_id' => $product->id,
                            'change_amount' => $delItem->quantity,
                            'new_quantity' => $product->stock_quantity,
                            'reason' => "Removed from Invoice #{$invoice->invoice_number}",
                        ]);
                    }
                }
            }
            $invoice->items()->whereNotIn('id', $itemIds)->delete();

            foreach ($validated['items'] as $index => $itemData) {
                $oldItem = null;
                if (!empty($itemData['id'])) {
                    $oldItem = $invoice->items()->find($itemData['id']);
                }

                $invoice->items()->updateOrCreate(
                    ['id' => $itemData['id'] ?? null],
                    [
                        'tenant_id' => $invoice->tenant_id,
                        'type' => $itemData['type'],
                        'title' => $itemData['title'],
                        'description' => $itemData['description'] ?? null,
                        'unit_price' => $itemData['unit_price'],
                        'quantity' => $itemData['quantity'],
                        'total' => $itemData['unit_price'] * $itemData['quantity'],
                        'sort_order' => $index,
                        'product_id' => $itemData['product_id'] ?? null,
                    ]
                );

                if ($oldItem) {
                    if ($oldItem->product_id && $oldItem->product_id == ($itemData['product_id'] ?? null)) {
                        $diff = $itemData['quantity'] - $oldItem->quantity;
                        if ($diff != 0) {
                            $product = Product::find($oldItem->product_id);
                            if ($product) {
                                $oldQuantity = (float) $product->stock_quantity;
                                $product->stock_quantity -= $diff;
                                $product->save();
                                ProductStockLog::create([
                                    'tenant_id' => $tenant->id,
                                    'product_id' => $product->id,
                                    'change_amount' => -$diff,
                                    'new_quantity' => $product->stock_quantity,
                                    'reason' => "Quantity updated on Invoice #{$invoice->invoice_number}",
                                ]);
                                if ($diff > 0) {
                                    $product->checkLowStock($oldQuantity);
                                }
                            }
                        }
                    } else {
                        if ($oldItem->product_id) {
                            $oldProduct = Product::find($oldItem->product_id);
                            if ($oldProduct) {
                                $oldProduct->stock_quantity += $oldItem->quantity;
                                $oldProduct->save();
                                ProductStockLog::create([
                                    'tenant_id' => $tenant->id,
                                    'product_id' => $oldItem->product_id,
                                    'change_amount' => $oldItem->quantity,
                                    'new_quantity' => $oldProduct->stock_quantity,
                                    'reason' => "Removed from Invoice #{$invoice->invoice_number}",
                                ]);
                            }
                        }
                        if (!empty($itemData['product_id'])) {
                            $newProduct = Product::find($itemData['product_id']);
                            if ($newProduct) {
                                $oldQuantity = (float) $newProduct->stock_quantity;
                                $newProduct->stock_quantity -= $itemData['quantity'];
                                $newProduct->save();
                                ProductStockLog::create([
                                    'tenant_id' => $tenant->id,
                                    'product_id' => $itemData['product_id'],
                                    'change_amount' => -$itemData['quantity'],
                                    'new_quantity' => $newProduct->stock_quantity,
                                    'reason' => "Added to Invoice #{$invoice->invoice_number}",
                                ]);
                                $newProduct->checkLowStock($oldQuantity);
                            }
                        }
                    }
                } else {
                    if (!empty($itemData['product_id'])) {
                        $newProduct = Product::find($itemData['product_id']);
                        if ($newProduct) {
                            $oldQuantity = (float) $newProduct->stock_quantity;
                            $newProduct->stock_quantity -= $itemData['quantity'];
                            $newProduct->save();
                            ProductStockLog::create([
                                'tenant_id' => $tenant->id,
                                'product_id' => $itemData['product_id'],
                                'change_amount' => -$itemData['quantity'],
                                'new_quantity' => $newProduct->stock_quantity,
                                'reason' => "Added to Invoice #{$invoice->invoice_number}",
                            ]);
                            $newProduct->checkLowStock($oldQuantity);
                        }
                    }
                }
            }

            // Sync costs
            $costIds = collect($validated['costs'] ?? [])->pluck('id')->filter()->toArray();
            $invoice->costs()->whereNotIn('id', $costIds)->delete();

            if (!empty($validated['costs'])) {
                foreach ($validated['costs'] as $costData) {
                    $costRate = $this->exchangeRateService->getRate($amountCurrency, $businessCurrency, $validated['issued_at']);
                    $invoice->costs()->updateOrCreate(
                        ['id' => $costData['id'] ?? null],
                        [
                            'tenant_id' => $invoice->tenant_id,
                            'title' => $costData['title'],
                            'amount' => $costData['amount'],
                            'currency_id' => $client->currency_id,
                            'business_amount' => $costData['amount'] * $costRate,
                            'business_currency_id' => $tenant->base_currency_id,
                            'exchange_rate' => $costRate,
                            'exchange_rate_date' => $validated['issued_at'],
                            'payment_status' => $costData['payment_status'] ?? 'unpaid',
                        ]
                    );
                }
            }

            return $invoice;
        });
    }
}
