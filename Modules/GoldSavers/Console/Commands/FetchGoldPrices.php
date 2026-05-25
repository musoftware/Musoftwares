<?php

namespace Modules\GoldSavers\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Modules\GoldSavers\Models\GoldPrice;

class FetchGoldPrices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gold-savers:fetch-prices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch live Egyptian gold prices from masrawy.com and store them.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $url  = 'https://www.masrawy.com/gold';
        $html = $this->fetchHtml($url);

        if (! $html) {
            $this->error('Failed to fetch gold price page.');
            return Command::FAILURE;
        }

        $dom = new \DOMDocument();
        @$dom->loadHTML($html);

        $goldPrices = [];
        $goldDivs   = $dom->getElementsByTagName('div');

        foreach ($goldDivs as $goldDiv) {
            if ($goldDiv->getAttribute('class') === 'currGoldDtls') {
                $h2    = $goldDiv->getElementsByTagName('h2')->item(0);
                $div   = $goldDiv->getElementsByTagName('div')->item(0);
                $carat = trim($h2->textContent);
                $price = str_replace(',', '', trim($div->textContent));
                $carat = str_replace('سعر الذهب عيار ', 'price_', $carat);
                $goldPrices[$carat] = $price;
            }
        }

        if (empty($goldPrices)) {
            $this->warn('No gold prices found — page structure may have changed.');
            return Command::FAILURE;
        }

        // Avoid duplicate entries for today if prices haven't changed
        $existing = GoldPrice::whereDate('price_date', Carbon::today())
            ->orderBy('id', 'desc')
            ->first();

        if ($existing &&
            $existing->price_14k == ($goldPrices['price_14'] ?? null) &&
            $existing->price_18k == ($goldPrices['price_18'] ?? null) &&
            $existing->price_21k == ($goldPrices['price_21'] ?? null) &&
            $existing->price_24k == ($goldPrices['price_24'] ?? null)
        ) {
            $this->info('Prices unchanged — skipping insert.');
            return Command::SUCCESS;
        }

        GoldPrice::create([
            'price_date' => Carbon::now()->format('Y-m-d H:i:s'),
            'price_10k'  => $goldPrices['price_10']  ?? null,
            'price_14k'  => $goldPrices['price_14']  ?? null,
            'price_18k'  => $goldPrices['price_18']  ?? null,
            'price_21k'  => $goldPrices['price_21']  ?? null,
            'price_22k'  => isset($goldPrices['price_21'])
                                ? round($goldPrices['price_21'] / 21 * 22, 2)
                                : null,
            'price_24k'  => $goldPrices['price_24']  ?? null,
        ]);

        $this->info('Gold prices saved successfully.');
        return Command::SUCCESS;
    }

    /**
     * Fetch raw HTML from a URL using Laravel HTTP or cURL fallback.
     */
    private function fetchHtml(string $url): ?string
    {
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(15)->get($url);
            return $response->successful() ? $response->body() : null;
        } catch (\Throwable $e) {
            $this->error('HTTP error: ' . $e->getMessage());
            return null;
        }
    }
}
