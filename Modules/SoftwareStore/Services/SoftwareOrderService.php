<?php

namespace Modules\SoftwareStore\Services;

use App\Builders\KashierCheckoutBuilder;
use App\Models\AdminSettings;
use App\Models\Currency;
use Modules\SoftwareStore\Models\SoftwareOrder;
use Modules\SoftwareStore\Models\SoftwareProduct;
use Modules\SoftwareStore\Models\WhiteLabelPartner;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

class SoftwareOrderService
{
    private const DEFAULT_OWNER_WHATSAPP = '201015218548';

    private const DOWNLOAD_LINK_HOURS = 48;

    public function __construct(private readonly SoftwareActivationService $activation, private readonly WhiteLabelService $whiteLabel) {}

    /** With a partner, the customer pays the partner's price and the partner's share is fixed now. */
    public function createPendingOrder(SoftwareProduct $product, array $client, ?WhiteLabelPartner $partner = null): SoftwareOrder
    {
        $plan = $product->planByKey($client['plan_key'] ?? null);
        $base = $product->salePrice($plan['key'] ?? null);
        $amount = $partner ? $this->whiteLabel->partnerPrice($partner, $product, $plan['key'] ?? null) : $base;

        return $product->orders()->create([
            'plan_key' => $plan['key'] ?? null,
            // stored only once the migration ran, so an order never fails because of this
            ...(Schema::hasColumn('software_orders', 'locale') ? ['locale' => app()->getLocale()] : []),
            'plan_name' => $plan['name'] ?? null,
            'client_name' => $client['client_name'],
            'client_email' => $client['client_email'],
            'client_whatsapp' => $client['client_whatsapp'],
            'device_id' => $client['device_id'] ?? null,
            'amount' => $amount,
            ...($partner ? [
                'white_label_partner_id' => $partner->id,
                'base_amount' => $base,
                'partner_commission' => $this->whiteLabel->commission($base, $amount),
            ] : []),
            'currency' => $product->saleCurrency(),
            'status' => SoftwareOrder::STATUS_PENDING,
        ]);
    }

    /** The Kashier checkout link for an unpaid order. Throws when the order's currency has no rate. */
    public function paymentUrl(SoftwareOrder $order): string
    {
        // The gateway converts to EGP with our own rates. An unknown currency would be charged as EGP, so stop.
        if ($order->currency !== 'EGP' && ! Currency::where('currency', $order->currency)->exists()) {
            throw new \RuntimeException("No exchange rate for {$order->currency}");
        }

        return KashierCheckoutBuilder::make()
            ->forAmount((float) $order->amount, $order->currency)
            ->forGuest($order->client_name, $order->client_email, 'swo_'.$order->id)
            ->withSource('software-order', 'swo_')
            ->withMetadata(['invoice_id' => $order->id, 'software_product_id' => $order->software_product_id])
            ->withRoutes(
                success: route('software.orders.success', $order->uuid),
                failure: route('software.orders.failure', $order->uuid),
                webhook: route('software.orders.webhook')
            )
            ->build();
    }

    public function markPaid(SoftwareOrder $order, ?string $transactionId): void
    {
        if ($order->isPaid()) {
            return;
        }

        $order->update([
            'status' => SoftwareOrder::STATUS_PAID,
            'paid_at' => now(),
            'payment_gateway' => 'kashier',
            'payment_reference' => $transactionId,
        ]);

        $this->activateLicense($order);
        $this->whiteLabel->recordCommission($order);
        $this->notifyOwner($order);
    }

    /** A failed mail must never undo a confirmed payment. */
    private function notifyOwner(SoftwareOrder $order): void
    {
        try {
            Mail::to(\App\Support\AdminRecipients::emails())->queue(new \App\Mail\SoftwareOrderPaidAdminMail($order->loadMissing('product')));
        } catch (\Throwable $e) {
            Log::warning('Software order: owner mail failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
        }
    }

    /** A failed activation must never undo a confirmed payment, so it is logged and can be retried by hand. */
    private function activateLicense(SoftwareOrder $order): void
    {
        try {
            $this->activation->activate($order);
        } catch (\Throwable $e) {
            Log::error('Software order: activation failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
        }
    }

    public function ownerWhatsappNumber(?SoftwareProduct $product = null): string
    {
        if ($product?->ownerWhatsapp()) {
            return $product->ownerWhatsapp();
        }

        $raw = (string) AdminSettings::GetValue('owner_whatsapp_number', self::DEFAULT_OWNER_WHATSAPP);

        return preg_replace('/\D+/', '', $raw) ?: self::DEFAULT_OWNER_WHATSAPP;
    }

    public function whatsappLink(SoftwareOrder $order): string
    {
        $message = implode("\n", array_filter([
            'Hello, I paid for a program.',
            'Order: '.$order->reference(),
            'Program: '.$order->product->name_en,
            $order->plan_name ? 'Plan: '.$order->plan_name : null,
            'Name: '.$order->client_name,
            'Email: '.$order->client_email,
            $order->device_id ? 'Device ID: '.$order->device_id : null,
        ]));

        // a partner's customer talks to the partner, never to us
        $number = $order->partner?->whatsappNumber() ?? $this->ownerWhatsappNumber($order->product);

        return 'https://wa.me/'.$number.'?text='.rawurlencode($message);
    }

    public function downloadLink(SoftwareOrder $order): string
    {
        return URL::temporarySignedRoute(
            'software.orders.download',
            now()->addHours(self::DOWNLOAD_LINK_HOURS),
            ['uuid' => $order->uuid]
        );
    }
}
