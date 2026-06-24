<?php

namespace App\Console\Commands;

use App\Helpers\CurlHelper;
use App\Models\GoldPrice;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GoldPriceFetcher extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gold_price:fetcher';

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
        $url = 'https://www.masrawy.com/gold';
        $html = CurlHelper::fetchHtmlContent($url);
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);
        // Array to store the extracted gold prices
        $goldPrices = [];

// Find all the div elements with the class "currGoldDtls"
        $goldDivs = $dom->getElementsByTagName('div');
        foreach ($goldDivs as $goldDiv) {
            // Check if the div has the class "currGoldDtls"
            if ($goldDiv->getAttribute('class') === 'currGoldDtls') {
                // Find the h2 element within the div
                $h2 = $goldDiv->getElementsByTagName('h2')->item(0);

                // Find the div element within the div
                $div = $goldDiv->getElementsByTagName('div')->item(0);

                // Get the text content of the h2 and div elements
                $carat = trim($h2->textContent);
                $price = trim($div->textContent);

                // Remove commas to avoid non-numeric value encountered errors
                $price = str_replace(',', '', $price);

                $carat = str_replace('سعر الذهب عيار ', 'price_', $carat);
                // Store the carat and price in the goldPrices array
                $goldPrices[$carat] = $price;
            }
        }

        $goldx = GoldPrice::whereDate('price_date', \Illuminate\Support\Carbon::today())->orderBy('id', 'desc')->first();

        if ($goldx != null){
            if ($goldx->price_14k == $goldPrices['price_14']
                && $goldx->price_18k == $goldPrices['price_18']
                && $goldx->price_21k == $goldPrices['price_21']
                && $goldx->price_24k == $goldPrices['price_24']) {
                return Command::SUCCESS;
            }
        }


        $goldprice = new GoldPrice();
        $goldprice->price_date = Carbon::now()->format('Y-m-d H:i:s');
        $goldprice->price_10k = $goldPrices['price_10'];
        $goldprice->price_14k = $goldPrices['price_14'];
        $goldprice->price_18k = $goldPrices['price_18'];
        $goldprice->price_21k = $goldPrices['price_21'];
        $goldprice->price_22k = $goldPrices['price_21'] / 21 * 22;
        $goldprice->price_24k = $goldPrices['price_24'];
        $goldprice->save();

        return Command::SUCCESS;
    }


}
