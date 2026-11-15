<?php

declare(strict_types=1);

namespace Modules\DigitalProducts\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\DigitalProducts\Models\DigitalProduct;
use Modules\DigitalProducts\Models\DigitalProductDownload;
use Modules\DigitalProducts\Models\DigitalProductPurchase;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Who downloaded and who bought one book, for the admin; the store page stays for customers. */
class AdminDigitalProductStatsController extends Controller
{
    private const PER_PAGE = 25;

    public function show(Request $request, int $id): Response
    {
        $product = DigitalProduct::findOrFail($id);
        $search = trim((string) $request->query('search', ''));

        return Inertia::render('Admin/DigitalProducts/Stats', [
            'product' => $product->only(['id', 'title', 'slug', 'is_free', 'has_free_edition', 'view_count', 'is_published']),
            'stats' => $this->stats($product),
            'downloads' => $this->downloads($product, $search)->paginate(self::PER_PAGE, ['*'], 'downloads_page')->withQueryString()
                ->through(fn (DigitalProductDownload $row) => [
                    'id' => $row->id,
                    'email' => $row->email,
                    'user' => $row->user?->only(['id', 'name']),
                    'edition' => $row->edition_type ?: 'full',
                    'times' => $row->download_count,
                    'requested_at' => $row->created_at?->toIso8601String(),
                    'last_downloaded_at' => $row->last_downloaded_at?->toIso8601String(),
                ]),
            'purchases' => DigitalProductPurchase::with(['user:id,name,email', 'currency'])->where('digital_product_id', $product->id)->latest()
                ->paginate(self::PER_PAGE, ['*'], 'purchases_page')->withQueryString()
                ->through(fn (DigitalProductPurchase $row) => [
                    'id' => $row->id,
                    'user' => $row->user?->only(['id', 'name', 'email']),
                    'amount' => (float) $row->amount_paid,
                    'currency' => $row->currency?->currency,
                    'method' => $row->payment_method,
                    'status' => $row->status,
                    'bought_at' => $row->created_at?->toIso8601String(),
                ]),
            'filters' => ['search' => $search],
        ]);
    }

    /** Every email that asked for this book, one line each, for a mailing list. */
    public function export(int $id): StreamedResponse
    {
        $product = DigitalProduct::findOrFail($id);

        return response()->streamDownload(function () use ($product) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['email', 'name', 'edition', 'times_downloaded', 'requested_at', 'last_downloaded_at']);
            $this->downloads($product, '')->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $row) {
                    fputcsv($out, [$row->email, $row->user?->name, $row->edition_type ?: 'full', $row->download_count, $row->created_at, $row->last_downloaded_at]);
                }
            });
            fclose($out);
        }, 'book-'.$product->slug.'-downloads.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function downloads(DigitalProduct $product, string $search): Builder
    {
        return DigitalProductDownload::with('user:id,name')
            ->where('digital_product_id', $product->id)
            ->when($search !== '', fn (Builder $q) => $q->where('email', 'like', '%'.$search.'%'))
            ->latest();
    }

    /** @return array<string, int|float> */
    private function stats(DigitalProduct $product): array
    {
        $downloads = DigitalProductDownload::where('digital_product_id', $product->id);
        $purchases = DigitalProductPurchase::where('digital_product_id', $product->id);

        return [
            'views' => (int) $product->view_count,
            'people' => (clone $downloads)->distinct()->count('email'),
            'requests' => (clone $downloads)->count(),
            'downloads' => (int) (clone $downloads)->sum('download_count'),
            'full_requests' => (clone $downloads)->where(fn (Builder $q) => $q->where('edition_type', 'full')->orWhereNull('edition_type'))->count(),
            'playbook_requests' => (clone $downloads)->where('edition_type', 'playbook')->count(),
            'purchases' => (clone $purchases)->count(),
            'paid_purchases' => (clone $purchases)->where('amount_paid', '>', 0)->count(),
        ];
    }
}
