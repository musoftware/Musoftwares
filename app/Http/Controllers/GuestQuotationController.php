<?php

namespace App\Http\Controllers;

use App\Builders\KashierCheckoutBuilder;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Quotation;
use App\Models\QuotationOrder;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;

class GuestQuotationController extends Controller
{
    /**
     * Display the public quotation page to the client.
     */
    public function show(Request $request, string $uuid)
    {
        $quotation = Quotation::with(['items', 'currencyModel'])
            ->where('uuid', $uuid)
            ->firstOrFail();

        // Increment view counters
        $quotation->increment('views_count');
        $quotation->updateQuietly(['last_viewed_at' => now()]);

        return Inertia::render('Guest/QuotationShow', [
            'quotation' => $quotation,
            'checkoutUrl' => route('guest.quotations.checkout', ['uuid' => $quotation->uuid]),
        ]);
    }

    /**
     * Show Step 2: Client checkout & contact info collection.
     */
    public function checkout(Request $request, string $uuid)
    {
        $quotation = Quotation::with(['items', 'currencyModel'])
            ->where('uuid', $uuid)
            ->firstOrFail();

        return Inertia::render('Guest/QuotationCheckout', [
            'quotation' => $quotation,
            'payUrl' => route('guest.quotations.pay', ['uuid' => $quotation->uuid]),
            'backUrl' => route('guest.quotations.show', ['uuid' => $quotation->uuid]),
        ]);
    }

    /**
     * Process client details, create account/invoice, and redirect to payment gateway.
     */
    public function initiatePayment(Request $request, string $uuid)
    {
        $quotation = Quotation::where('uuid', $uuid)->firstOrFail();

        $validated = $request->validate([
            'client_name' => 'required|string|max:255',
            'client_email' => 'required|email|max:255',
            'client_phone' => 'nullable|string|max:50',
            'client_whatsapp' => 'nullable|string|max:50',
            'company_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $depositAmount = (float) $quotation->deposit_amount;
        if ($depositAmount <= 0) {
            return back()->with('error', 'قيمة الدفعة المقدمة غير صالحة.');
        }

        $order = DB::transaction(function () use ($quotation, $validated, $depositAmount) {
            // 1. Find or create client User account
            $user = User::where('email', $validated['client_email'])->first();
            if (!$user) {
                $user = User::create([
                    'name' => $validated['client_name'],
                    'email' => $validated['client_email'],
                    'password' => Hash::make(Str::random(16)),
                    'mobile_1' => $validated['client_phone'] ?? null,
                    'whatsapp_number' => $validated['client_whatsapp'] ?? $validated['client_phone'] ?? null,
                    'currency_id' => $quotation->currency_id,
                ]);

                if (method_exists($user, 'assignRole')) {
                    try {
                        $user->assignRole('client');
                    } catch (\Throwable $e) {
                        // Role might already be synced or assigned
                    }
                }
            } else {
                // Update phone/whatsapp if missing
                $updateData = [];
                if (empty($user->mobile_1) && !empty($validated['client_phone'])) {
                    $updateData['mobile_1'] = $validated['client_phone'];
                }
                if (empty($user->whatsapp_number) && !empty($validated['client_whatsapp'])) {
                    $updateData['whatsapp_number'] = $validated['client_whatsapp'];
                }
                if (!empty($updateData)) {
                    $user->update($updateData);
                }
            }

            // 2. Create official Invoice for the 50% deposit
            $currencyId = $quotation->currency_id ?? ($user->currency_id ?? 1);
            $invoice = Invoice::create([
                'user_id' => $user->id,
                'status' => 'unpaid',
                'currency' => $currencyId,
            ]);

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'item_title' => "دفعة مقدمة ({$quotation->deposit_percentage}%) - {$quotation->title}",
                'amount' => $depositAmount,
                'qty' => 1,
                'item_type' => 'simple',
            ]);

            // 3. Create QuotationOrder record
            $quotationOrder = QuotationOrder::create([
                'quotation_id' => $quotation->id,
                'user_id' => $user->id,
                'client_name' => $validated['client_name'],
                'client_email' => $validated['client_email'],
                'client_phone' => $validated['client_phone'] ?? null,
                'client_whatsapp' => $validated['client_whatsapp'] ?? null,
                'company_name' => $validated['company_name'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'deposit_amount' => $depositAmount,
                'currency' => $quotation->currency,
                'status' => 'pending_payment',
                'invoice_id' => $invoice->id,
            ]);

            return $quotationOrder;
        });

        // 4. Build Payment URL via Kashier
        try {
            $currencyModel = Currency::findCached($quotation->currency_id ?? $quotation->currency);
            $currencyCode = $currencyModel ? $currencyModel->currency : $quotation->currency;

            $paymentUrl = KashierCheckoutBuilder::make()
                ->forAmount($depositAmount, $currencyCode)
                ->forGuest($validated['client_name'], $validated['client_email'], 'qto_' . $order->id)
                ->withSource('quotation-deposit', 'qto_')
                ->withMetadata([
                    'quotation_id' => $quotation->id,
                    'order_id' => $order->id,
                    'invoice_id' => $order->invoice_id,
                    'user_id' => $order->user_id,
                ])
                ->withRoutes(
                    success: route('guest.quotations.payment.success', ['orderUuid' => $order->uuid]),
                    failure: route('guest.quotations.payment.failure', ['orderUuid' => $order->uuid]),
                    webhook: route('guest.quotations.payment.webhook')
                )
                ->build();

            return Inertia::location($paymentUrl);
        } catch (\Throwable $e) {
            Log::error('Quotation Payment initiation error: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'quotation_id' => $quotation->id,
            ]);

            return back()->with('error', 'حدث خطأ أثناء الاتصال ببوابة الدفع: ' . $e->getMessage());
        }
    }

    /**
     * Handle payment success callback.
     */
    public function paymentSuccess(Request $request, string $orderUuid)
    {
        $order = QuotationOrder::with(['quotation', 'user', 'invoice'])->where('uuid', $orderUuid)->firstOrFail();

        if ($order->status !== 'paid') {
            DB::transaction(function () use ($order, $request) {
                $order->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'payment_gateway' => 'kashier',
                    'payment_reference' => $request->input('paymentId') ?? $request->input('transactionId') ?? $request->input('signature'),
                ]);

                if ($order->invoice && $order->invoice->status !== 'paid') {
                    $order->invoice->update([
                        'status' => 'paid',
                        'paid_at' => now(),
                    ]);
                }
            });
        }

        return Inertia::render('Guest/QuotationPaymentResult', [
            'status' => 'success',
            'order' => $order,
            'message' => 'تم استلام الدفعة المقدمة بنجاح! تم إنشاء حسابك وتأكيد بدء العمل على المشروع.',
        ]);
    }

    /**
     * Handle payment failure callback.
     */
    public function paymentFailure(Request $request, string $orderUuid)
    {
        $order = QuotationOrder::with('quotation')->where('uuid', $orderUuid)->firstOrFail();

        return Inertia::render('Guest/QuotationPaymentResult', [
            'status' => 'failed',
            'order' => $order,
            'message' => 'تعذر إتمام عملية الدفع. يرجى المحاولة مرة أخرى أو استخدام وسيلة دفع أخرى.',
            'retryUrl' => route('guest.quotations.checkout', ['uuid' => $order->quotation->uuid]),
        ]);
    }

    /**
     * Webhook for payment gateway asynchronous confirmation.
     */
    public function webhook(Request $request)
    {
        $orderId = $request->input('order_id') ?? $request->input('merchantOrderId');
        if (!$orderId) {
            return response()->json(['status' => 'ignored'], 200);
        }

        // Clean prefix if present
        $cleanId = str_replace('qto_', '', $orderId);
        $order = QuotationOrder::find($cleanId);

        if ($order && $order->status !== 'paid') {
            DB::transaction(function () use ($order, $request) {
                $order->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'payment_gateway' => 'kashier',
                    'payment_reference' => $request->input('paymentId'),
                ]);

                if ($order->invoice && $order->invoice->status !== 'paid') {
                    $order->invoice->update([
                        'status' => 'paid',
                        'paid_at' => now(),
                    ]);
                }
            });
        }

        return response()->json(['status' => 'processed'], 200);
    }
}
