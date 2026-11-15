<?php

namespace App\Http\Controllers;

use App\Models\MicroService;
use App\Models\MicroServiceOrder;
use App\Support\LocaleUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * The public page of small services, written on the server so search engines and AI assistants read every service
 * without signing in. Each service is a title, a short description and a real example; the visitor contacts us or
 * starts with the AI assistant, which can build the whole thing.
 */
class MicroServiceCatalogController extends Controller
{
    public function index(Request $request): View
    {
        $services = MicroService::active()->get(['id', 'title', 'description', 'example_url']);
        $canonical = LocaleUrl::canonical(route('micro-services.index'));

        return view('micro-services.index', [
            'services' => $services,
            'canonical' => $canonical,
            'schema' => $this->schema($services, $canonical),
            'hasOrders' => $request->user() !== null && MicroServiceOrder::where('user_id', $request->user()->id)->exists(),
        ]);
    }

    /** One list of services and the path to the page, for search engines and AI assistants. */
    private function schema(Collection $services, string $canonical): array
    {
        $provider = ['@type' => 'Organization', 'name' => 'Musoftwares', 'url' => url('/')];

        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'ItemList',
                    'name' => __('micro_services.title'),
                    'description' => __('micro_services.meta_description'),
                    'url' => $canonical,
                    'itemListElement' => $services->values()->map(fn (MicroService $service, int $index) => [
                        '@type' => 'ListItem',
                        'position' => $index + 1,
                        'item' => array_filter([
                            '@type' => 'Service',
                            'name' => $service->title,
                            'description' => $service->description,
                            'url' => $canonical.'#service-'.$service->id,
                            'subjectOf' => $service->example_url ? ['@type' => 'WebPage', 'url' => $service->example_url] : null,
                            'provider' => $provider,
                        ]),
                    ])->all(),
                ],
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Musoftwares', 'item' => url('/')],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => __('micro_services.title'), 'item' => $canonical],
                    ],
                ],
            ],
        ];
    }
}
