<?php

namespace Modules\ERP\Services;

use Modules\ERP\Models\Invoice;
use Modules\ERP\Models\TenantClient;
use Modules\ERP\Models\Tenant;
use Modules\ERP\Models\WalletTransaction;
use App\Models\Currency;
use App\Models\CurrenciesExchange;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PosService
{
    protected $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    public function processCheckout(Tenant $tenant, array $validated): Invoice
    {
        return DB::transaction(function () use ($tenant, $validated) {
            $clientId = $validated['client_id'] ?? null;
            
            // Handle Walk-in Client
            if (!$clientId) {
                $walkInClient = TenantClient::firstOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'name' => 'Walk-in Client',
                    ],
                    [
                        'currency_id' => $tenant->base_currency_id,
                        'type' => 'individual',
                        'email' => 'walkin@example.com',
                        'phone' => '0000000000',
                    ]
                );
                $clientId = $walkInClient->id;
            }

            $client = TenantClient::find($clientId);

            // Create Invoice using InvoiceService
            $invoiceItems = [];
            foreach ($validated['items'] as $item) {
                $product = \Modules\ERP\Models\Product::find($item['product_id']);
                $invoiceItems[] = [
                    'type' => 'product',
                    'title' => $product ? $product->name : 'POS Item',
                    'unit_price' => $item['unit_price'],
                    'quantity' => $item['quantity'],
                    'product_id' => $item['product_id'],
                ];
            }

            $invoiceData = [
                'client_id' => $clientId,
                'invoice_number' => $this->generateInvoiceNumber($tenant),
                'issued_at' => now()->toDateString(),
                'due_date' => now()->toDateString(),
                'items' => $invoiceItems,
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'notes' => __('erp.pos_sale_notes', ['method' => $validated['payment_method']]),
            ];

            $invoice = $this->invoiceService->createInvoice($invoiceData, $tenant);
            
            // Handle Payment Status
            if (!empty($validated['is_paid'])) {
                $invoice->status = 'paid';
                $invoice->paid_amount = $invoice->amount;
                $invoice->save();

                $businessAmount = CurrenciesExchange::RateByDate(
                    now(),
                    $invoice->amount,
                    $client->currency_id,
                    $tenant->base_currency_id
                );

                WalletTransaction::create([
                    'tenant_id'        => $tenant->id,
                    'client_id'        => $clientId,
                    'type'             => 'received',
                    'direction'        => 'credit',
                    'amount'           => $invoice->amount,
                    'currency_id'      => $client->currency_id,
                    'business_amount'  => $businessAmount,
                    'business_currency_id' => $tenant->base_currency_id,
                    'exchange_rate'    => CurrenciesExchange::Rate(now()->toDateString(), $client->currency_id, $tenant->base_currency_id),
                    'exchange_rate_date'=> now()->toDateString(),
                    'reference_type'   => 'invoice_payment',
                    'reference_id'     => $invoice->id,
                    'note'             => __('erp.pos_payment_received_note', ['invoice' => $invoice->invoice_number, 'method' => $validated['payment_method']]),
                    'created_by'       => Auth::id(),
                ]);
            } else {
                $invoice->status = 'sent'; // Assuming sent means pending/unpaid
                $invoice->save();
            }

            // Create Expense (Cost Transaction) for products sold
            foreach ($validated['items'] as $item) {
                $product = \Modules\ERP\Models\Product::find($item['product_id']);
                if ($product && $product->cost_price > 0) {
                    $totalCost = $product->cost_price * $item['quantity'];
                    \Modules\ERP\Models\Expense::create([
                        'tenant_id' => $tenant->id,
                        'client_id' => $clientId,
                        'title' => __('erp.cogs_expense_title', ['product' => $product->name, 'invoice' => $invoice->invoice_number]),
                        'amount' => $totalCost, // Assumed in business currency
                        'category' => 'COGS',
                        'date' => now()->toDateString(),
                        'description' => "Cost of Goods Sold for POS Invoice {$invoice->invoice_number}. Quantity: {$item['quantity']}",
                        'created_by' => Auth::id(),
                    ]);
                }
            }

            return $invoice;
        });
    }

    private function generateInvoiceNumber(Tenant $tenant): string
    {
        $lastInvoice = Invoice::where('tenant_id', $tenant->id)
            ->orderBy('id', 'desc')
            ->first();

        if (!$lastInvoice) {
            return 'INV-0001';
        }

        $lastNumber = intval(preg_replace('/[^0-9]/', '', $lastInvoice->invoice_number));
        return 'INV-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }
}
