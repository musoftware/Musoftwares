<?php

namespace Modules\Shop\Console;

use Illuminate\Console\Command;
use Modules\Shop\Models\ShopOrder;
use Modules\Shop\Services\Angazny\AngaznyClient;
use Modules\Shop\Services\Angazny\AngaznyOrderSender;

class SendOrdersToAngazny extends Command
{
    protected $signature = 'shop:angazny-send-orders';

    protected $description = 'Send waiting store orders to Angazny, when automatic sending is switched on';

    public function handle(AngaznyClient $client, AngaznyOrderSender $sender): int
    {
        if (! config('shop.angazny.forward_orders') || ! $client->isConfigured()) {
            return self::SUCCESS;
        }

        ShopOrder::with('items')
            ->where('angazny_status', ShopOrder::ANGAZNY_PENDING)
            ->orderBy('id')
            ->each(fn (ShopOrder $order) => $sender->send($order));

        return self::SUCCESS;
    }
}
