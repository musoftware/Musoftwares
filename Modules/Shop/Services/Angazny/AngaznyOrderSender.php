<?php

namespace Modules\Shop\Services\Angazny;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use Modules\Shop\Models\ShopOrder;
use Modules\Shop\Models\ShopOrderItem;

/**
 * Sends a store order to the warehouse, at most once.
 * If the answer never arrives the order may already exist there, so it is marked "unknown"
 * and waits for a person to check. It is never resent automatically.
 */
class AngaznyOrderSender
{
    private const ERROR_LENGTH = 500;

    public function __construct(private readonly AngaznyClient $client) {}

    public function send(ShopOrder $order): void
    {
        if (! $this->claim($order)) {
            return;
        }

        $problem = $this->problemWith($order);
        if ($problem !== null) {
            $this->finish($order, ShopOrder::ANGAZNY_FAILED, $problem);

            return;
        }

        $this->deliver($order);
    }

    /** Only one process can move an order to "sending", so two runs can't both send it. */
    private function claim(ShopOrder $order): bool
    {
        $claimed = ShopOrder::whereKey($order->id)
            ->whereIn('angazny_status', ShopOrder::ANGAZNY_SENDABLE)
            ->where('status', '!=', 'cancelled')
            ->update(['angazny_status' => ShopOrder::ANGAZNY_SENDING, 'angazny_error' => null]);

        return $claimed === 1;
    }

    private function problemWith(ShopOrder $order): ?string
    {
        if ($order->items->contains(fn (ShopOrderItem $item) => $item->angazny_stock_id === null)) {
            return 'الطلب فيه منتج مش من انجزني، لازم يتراجع يدوي';
        }

        if ($this->cityId($order) === null) {
            return 'المحافظة دي مش متاحة عند انجزني';
        }

        return $order->angazny_area_id === null ? 'الطلب من غير منطقة، لازم يتراجع يدوي' : null;
    }

    private function deliver(ShopOrder $order): void
    {
        try {
            $this->record($order, $this->client->createOrder($this->payload($order)));
        } catch (ConnectionException $exception) {
            $this->unknownResult($order, $exception->getMessage());
        } catch (RequestException $exception) {
            $this->rejected($order, $exception);
        }
    }

    private function rejected(ShopOrder $order, RequestException $exception): void
    {
        if ($exception->response->serverError()) {
            $this->unknownResult($order, 'HTTP '.$exception->response->status());

            return;
        }

        $messages = (array) ($exception->response->json('messages') ?? ['HTTP '.$exception->response->status()]);
        $this->finish($order, ShopOrder::ANGAZNY_FAILED, implode(' - ', $messages));
    }

    private function unknownResult(ShopOrder $order, string $detail): void
    {
        Log::warning('Angazny order result unknown', ['order_id' => $order->id, 'detail' => $detail]);
        $this->finish($order, ShopOrder::ANGAZNY_UNKNOWN, 'الرد ما وصلش. راجع انجزني قبل أي إرسال تاني');
    }

    private function record(ShopOrder $order, array $response): void
    {
        $code = $response['order_code'] ?? null;
        if ($code === null) {
            $this->finish($order, ShopOrder::ANGAZNY_FAILED, implode(' - ', (array) ($response['messages'] ?? ['رد غير متوقع'])));

            return;
        }

        $missing = (int) ($response['itamsOutOfStock'] ?? 0) + (int) ($response['itamsNotFound'] ?? 0);
        $note = $missing > 0 ? 'وصل ناقص '.$missing.' منتج: '.implode(' - ', (array) ($response['messages'] ?? [])) : null;
        $this->finish($order, ShopOrder::ANGAZNY_SENT, $note, (string) $code);
    }

    private function finish(ShopOrder $order, string $status, ?string $error, ?string $code = null): void
    {
        $order->update([
            'angazny_status' => $status,
            'angazny_error' => $error === null ? null : mb_substr($error, 0, self::ERROR_LENGTH),
            'angazny_order_code' => $code,
        ]);
    }

    private function payload(ShopOrder $order): array
    {
        return [
            'name' => $order->customer_name,
            'city_id' => $this->cityId($order),
            'area_id' => $order->angazny_area_id,
            'address' => $order->address,
            'phone' => ['+2'.$order->phone],
            'notes' => $order->notes,
            'store' => config('shop.name'),
            'confirmed' => false,
            'items' => $order->items->map(fn (ShopOrderItem $item) => [
                'stock_id' => $item->angazny_stock_id,
                'quantity' => $item->quantity,
                'price_effect' => (float) $item->angazny_price_effect,
            ])->values()->all(),
        ];
    }

    private function cityId(ShopOrder $order): ?int
    {
        return config('shop.angazny.cities')[$order->governorate] ?? null;
    }
}
