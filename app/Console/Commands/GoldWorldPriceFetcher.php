<?php

namespace App\Console\Commands;

use App\Models\GoldPrice;
use App\Models\GoldWorldPrice;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class  GoldWorldPriceFetcher extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gold_world_price:fetcher';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $gram = Http::withHeaders([
            'X-API-KEY' => '5467fcfed75b11faf16a5ceaf99440fc5467fcfe',
        ])->get('http://goldpricez.com/api/rates/currency/egp/measure/gram');

        $j = \json_decode($gram->json(), true);

        $carat = $j['gram_in_egp'];

        $goldPrices['price_24'] = round($carat, 2);
        $goldPrices['price_21'] = round($carat * 21 / 24, 2);
        $goldPrices['price_18'] = round($carat * 18 / 24, 2);
        $goldPrices['price_14'] = round($carat * 14 / 24, 2);

        $goldx = GoldWorldPrice::whereDate('price_date', \Illuminate\Support\Carbon::today())->orderBy('id', 'desc')->first();

        if ($goldx != null) {
            if ($goldx->price_14k == $goldPrices['price_14']
                && $goldx->price_18k == $goldPrices['price_18']
                && $goldx->price_21k == $goldPrices['price_21']
                && $goldx->price_24k == $goldPrices['price_24']) {
                return Command::SUCCESS;
            }
        }


        $goldprice = new GoldWorldPrice();
        $goldprice->price_date = Carbon::now()->format('Y-m-d H:i:s');
        $goldprice->price_14k = $goldPrices['price_14'];
        $goldprice->price_18k = $goldPrices['price_18'];
        $goldprice->price_21k = $goldPrices['price_21'];
        $goldprice->price_22k = $goldPrices['price_21'] / 21 * 22;
        $goldprice->price_24k = $goldPrices['price_24'];
        $goldprice->save();

        return Command::SUCCESS;
    }
}
