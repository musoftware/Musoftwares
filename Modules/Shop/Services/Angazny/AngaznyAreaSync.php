<?php

namespace Modules\Shop\Services\Angazny;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use Modules\Shop\Models\ShopArea;

/** Keeps the delivery areas in step with Angazny, one governorate at a time. */
class AngaznyAreaSync
{
    private const PAUSE_MICROSECONDS = 300000;

    public function __construct(private readonly AngaznyClient $client) {}

    /** @return array{areas: int, failed_cities: list<int>} */
    public function handle(): array
    {
        $report = ['areas' => 0, 'failed_cities' => []];

        foreach (config('shop.angazny.cities') as $cityId) {
            $report = $this->syncCity((int) $cityId, $report);
            usleep(app()->runningUnitTests() ? 0 : self::PAUSE_MICROSECONDS);
        }

        return $report;
    }

    private function syncCity(int $cityId, array $report): array
    {
        try {
            $areas = $this->client->areas($cityId);
        } catch (RequestException|ConnectionException $exception) {
            Log::warning('Angazny areas could not be read', ['city_id' => $cityId, 'error' => $exception->getMessage()]);
            $report['failed_cities'][] = $cityId;

            return $report;
        }

        if ($areas === []) {
            return $report;
        }

        foreach ($areas as $area) {
            $this->store($cityId, $area);
        }

        ShopArea::where('city_id', $cityId)->whereNotIn('angazny_id', array_column($areas, 'id'))->delete();
        $report['areas'] += count($areas);

        return $report;
    }

    private function store(int $cityId, array $area): void
    {
        ShopArea::withTrashed()->updateOrCreate(
            ['angazny_id' => (int) $area['id']],
            ['city_id' => $cityId, 'name' => trim((string) $area['Name']), 'deleted_at' => null],
        );
    }
}
