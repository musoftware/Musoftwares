<?php

namespace Modules\GoldSavers\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Modules\GoldSavers\Models\GoldPrice;

class FetchWorldGoldPrices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gold-savers:fetch-world-prices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch international gold prices (USD/EGP per gram) from goldpricez.com and store them.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $apiKey = config('gold-savers.goldpricez_api_key', '5467fcfed75b11faf16a5ceaf99440fc5467fcfe');

        try {
            $response = Http::withHeaders(['X-API-KEY' => $apiKey])
                ->timeout(15)
                ->get('http://goldpricez.com/api/rates/currency/egp/measure/gram');
        } catch (\Throwable $e) {
            $this->error('Failed to reach goldpricez API: ' . $e->getMessage());
            return Command::FAILURE;
        }

        if (! $response->successful()) {
            $this->error('API returned status ' . $response->status());
            return Command::FAILURE;
        }

        $data = $response->json();

        if (! isset($data['gram_in_egp'])) {
            $this->error('Unexpected API response format.');
            return Command::FAILURE;
        }

        $gramEgp = (float) $data['gram_in_egp'];

        $goldPrices = [
            'price_24' => round($gramEgp, 2),
            'price_21' => round($gramEgp * 21 / 24, 2),
            'price_18' => round($gramEgp * 18 / 24, 2),
            'price_14' => round($gramEgp * 14 / 24, 2),
        ];

        // Avoid duplicate entries for today if prices haven't changed
        $existing = GoldPrice::whereDate('price_date', Carbon::today())
            ->orderBy('id', 'desc')
            ->first();

        if ($existing &&
            $existing->price_14k == $goldPrices['price_14'] &&
            $existing->price_18k == $goldPrices['price_18'] &&
            $existing->price_21k == $goldPrices['price_21'] &&
            $existing->price_24k == $goldPrices['price_24']
        ) {
            $this->info('World prices unchanged — skipping insert.');
            return Command::SUCCESS;
        }

        GoldPrice::create([
            'price_date' => Carbon::now()->format('Y-m-d H:i:s'),
            'price_14k'  => $goldPrices['price_14'],
            'price_18k'  => $goldPrices['price_18'],
            'price_21k'  => $goldPrices['price_21'],
            'price_22k'  => round($goldPrices['price_21'] / 21 * 22, 2),
            'price_24k'  => $goldPrices['price_24'],
        ]);

        $this->info('World gold prices saved successfully.');
        return Command::SUCCESS;
    }
}
