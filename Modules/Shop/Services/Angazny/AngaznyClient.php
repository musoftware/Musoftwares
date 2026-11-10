<?php

namespace Modules\Shop\Services\Angazny;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/** The only class that talks to the Angazny affiliate API. */
class AngaznyClient
{
    private const MAX_PAGES = 100;

    private const PER_PAGE = 100;

    private const TIMEOUT_SECONDS = 30;

    /** Angazny's product view often answers 500 for a moment and then works. */
    private const READ_ATTEMPTS = 3;

    private const READ_RETRY_MILLISECONDS = 2000;

    public function isConfigured(): bool
    {
        return (string) config('shop.angazny.token') !== '';
    }

    /** @return list<array<string, mixed>> */
    public function products(): array
    {
        $rows = [];

        for ($page = 1; $page <= self::MAX_PAGES; $page++) {
            $payload = $this->request()
                ->get('/affiliate/product/list/all', ['page' => $page, 'per_page' => self::PER_PAGE])
                ->throw()
                ->json();
            $rows = [...$rows, ...($payload['data'] ?? [])];

            if ($page >= (int) ($payload['pagination']['total_pages'] ?? 1)) {
                break;
            }
        }

        return $rows;
    }

    /** @return array<int, string> Angazny category id => name */
    public function categories(): array
    {
        $rows = $this->request()->get('/affiliate/product/category/list')->throw()->json('data') ?? [];

        return array_column($rows, 'category_name', 'id');
    }

    /** @return list<array{id: int, Name: string, price: float}> */
    public function areas(int $cityId): array
    {
        return $this->request()
            ->retry(self::READ_ATTEMPTS, self::READ_RETRY_MILLISECONDS)
            ->get('/affiliate/area/list/'.$cityId)
            ->throw()
            ->json('data') ?? [];
    }

    /** @return array<string, mixed> */
    public function product(int $id): array
    {
        return $this->request()
            ->retry(self::READ_ATTEMPTS, self::READ_RETRY_MILLISECONDS)
            ->get('/affiliate/product/view/'.$id)
            ->throw()
            ->json('data') ?? [];
    }

    /**
     * Sent once, with no automatic retry: a repeated request could ship the same order twice.
     *
     * @param  array<string, mixed>  $order
     * @return array<string, mixed>
     */
    public function createOrder(array $order): array
    {
        return $this->request()->post('/affiliate/order/create', $order)->throw()->json() ?? [];
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl((string) config('shop.angazny.base_url'))
            ->withToken((string) config('shop.angazny.token'))
            ->withUserAgent('MusoftwareStore/1.0')
            ->acceptJson()
            ->timeout(self::TIMEOUT_SECONDS);
    }
}
