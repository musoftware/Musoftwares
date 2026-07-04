<?php

namespace App\Services;

use App\Events\InvoiceItemAdded;
use App\Models\Invoice;
use App\Models\InvoiceCostLine;
use App\Models\InvoiceItem;
use App\Models\InvoiceItemTimer;

class InvoiceService extends BaseService
{
    public function updateInvoice(Invoice $invoice, array $data): void
    {
        if ($invoice->status !== 'unpaid') {
            throw new \Exception('Cannot edit items on a paid/cancelled invoice.');
        }

        $this->executeInTransaction(function () use ($invoice, $data) {
            if (isset($data['discount'])) {
                $invoice->update(['discount' => $data['discount']]);
            }

            if (isset($data['second_discount'])) {
                $invoice->update(['second_discount' => $data['second_discount']]);
            }

            // Will delete items after reassignment

            if (! empty($data['items'])) {
                foreach ($data['items'] as $itemData) {
                    if (! empty($itemData['id'])) {
                        // Update existing
                        $item = InvoiceItem::where('id', $itemData['id'])->where('invoice_id', $invoice->id)->first();
                        if ($item) {
                            $item->update([
                                'item_title' => $itemData['item_title'],
                                'amount' => $itemData['amount'],
                                'qty' => $itemData['qty'],
                                'item_type' => $itemData['item_type'] ?? $item->item_type,
                            ]);
                        }
                    } else {
                        // Create new
                        $item = InvoiceItem::create([
                            'invoice_id' => $invoice->id,
                            'item_title' => $itemData['item_title'],
                            'amount' => $itemData['amount'],
                            'qty' => $itemData['qty'],
                            'item_type' => $itemData['item_type'],
                            'currency' => $invoice->currency,
                        ]);

                        InvoiceItemAdded::dispatch($invoice, $item);
                    }

                    // Re-assign timers if this item is a merge of other timer items
                    if ($item && ! empty($itemData['merged_from'])) {
                        InvoiceItemTimer::whereIn('invoice_item_id', $itemData['merged_from'])
                            ->update(['invoice_item_id' => $item->id]);
                    }
                }
            }

            if (! empty($data['deleted_items'])) {
                InvoiceItem::whereIn('id', $data['deleted_items'])->where('invoice_id', $invoice->id)->delete();
            }

            if (! empty($data['deleted_cost_lines'])) {
                InvoiceCostLine::whereIn('id', $data['deleted_cost_lines'])->where('invoice_id', $invoice->id)->delete();
            }

            if (! empty($data['cost_lines'])) {
                foreach ($data['cost_lines'] as $lineData) {
                    if (! empty($lineData['id'])) {
                        // Update existing
                        $costLine = InvoiceCostLine::where('id', $lineData['id'])->where('invoice_id', $invoice->id)->first();
                        if ($costLine) {
                            $costLine->update([
                                'line_type' => $lineData['line_type'],
                                'amount' => $lineData['amount'],
                                'description' => $lineData['description'] ?? null,
                                'credit_user_id' => $lineData['credit_user_id'] ?? null,
                            ]);
                        }
                    } else {
                        // Create new
                        InvoiceCostLine::create([
                            'invoice_id' => $invoice->id,
                            'line_type' => $lineData['line_type'],
                            'amount' => $lineData['amount'],
                            'description' => $lineData['description'] ?? null,
                            'credit_user_id' => $lineData['credit_user_id'] ?? null,
                        ]);
                    }
                }
            }

            // Recalculate invoice totals via helper or model methods if necessary
            $invoice = $invoice->fresh();

            // Recalculate cost column based on current cost lines
            $invoice->cost = (float) InvoiceCostLine::where('invoice_id', $invoice->id)->sum('amount');

            if ($invoice->status === 'unpaid') {
                $invoice->unpaid = $invoice->total();
            }
            $invoice->save();
        });
    }

    public function markPaid(Invoice $invoice): void
    {
        if ($invoice->status === 'paid') {
            throw new \Exception('Invoice is already paid.');
        }

        $invoice->mark_as_paid();
    }

    public function cancelInvoice(Invoice $invoice): void
    {
        if ($invoice->status === 'cancelled') {
            throw new \Exception('Invoice is already cancelled.');
        }

        $invoice->cancel_invoice();
    }
}
