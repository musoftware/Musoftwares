@extends('marketplace::layouts.marketplace-master')

{{-- Same look as /services: a centred title, one search box, simple category links and plain cards. --}}
@section('content')
@php
    $isAr = app()->getLocale() === 'ar';
    $activeCategory = request('category') ?? ($filters['category'] ?? null);
    $keep = fn (array $extra = []) => array_filter(array_merge(request()->only(['search', 'sort', 'delivery_time', 'category']), $extra), fn ($v) => $v !== null && $v !== '');
@endphp
<div class="w-full overflow-x-clip bg-[#fbfbfd] text-[#1d1d1f] pt-12 sm:pt-20 pb-20">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 text-center">
        <h1 class="text-3xl sm:text-5xl font-bold tracking-tight">{{ __('marketplace_ui.title') }}</h1>
        <p class="mt-4 text-base text-[#1d1d1f]/60">{{ __('marketplace_ui.subtitle') }}</p>

        <form action="{{ route('marketplace.services.index') }}" method="GET" class="mt-8 relative max-w-[560px] mx-auto">
            @foreach ($keep() as $name => $value)
                @if ($name !== 'search')<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endif
            @endforeach
            <input type="search" name="search" value="{{ request('search') ?? request('q') }}" placeholder="{{ __('marketplace_ui.search') }}" aria-label="{{ __('marketplace_ui.search') }}"
                   class="w-full h-12 ps-5 pe-14 rounded-full bg-white border border-black/10 shadow-sm text-[15px] placeholder-[#86868b] focus:outline-none focus:border-[#0071e3]">
            <button type="submit" class="absolute top-2 end-2 w-8 h-8 rounded-full bg-[#0071e3] text-white flex items-center justify-center hover:bg-[#0077ed]" aria-label="{{ __('marketplace_ui.search') }}">
                <i class="ri-search-2-line text-sm" aria-hidden="true"></i>
            </button>
        </form>
    </div>

    <div class="max-w-[1100px] mx-auto px-4 sm:px-6 mt-10">
        {{-- Categories as plain links, like the section links on /services --}}
        <nav class="flex flex-wrap justify-center gap-x-6 gap-y-2 text-sm font-semibold" aria-label="{{ __('marketplace_ui.title') }}">
            <a href="{{ route('marketplace.services.index', $keep(['category' => null, 'page' => null])) }}"
               class="{{ $activeCategory ? 'text-[#1d1d1f]/60 hover:text-[#0071e3]' : 'text-[#0071e3]' }}">{{ __('marketplace_ui.all') }}</a>
            @foreach (($categories ?? collect()) as $category)
                <a href="{{ route('marketplace.services.index', $keep(['category' => $category->slug])) }}"
                   class="{{ $activeCategory === $category->slug ? 'text-[#0071e3]' : 'text-[#1d1d1f]/60 hover:text-[#0071e3]' }}">{{ $category->localizedName() }}</a>
            @endforeach
        </nav>

        <form action="{{ route('marketplace.services.index') }}" method="GET" id="catalogSort" class="mt-6 flex flex-wrap items-center justify-between gap-3">
            @foreach ($keep() as $name => $value)
                @if (! in_array($name, ['sort', 'delivery_time'], true))<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endif
            @endforeach
            <p class="text-sm text-[#1d1d1f]/60">{{ __('marketplace_ui.results', ['count' => $services->total()]) }}</p>
            <div class="flex items-center gap-2">
                <label class="sr-only" for="catalogSortBy">{{ __('marketplace_ui.sort') }}</label>
                <select id="catalogSortBy" name="sort" onchange="this.form.submit()" class="h-9 rounded-full border border-black/10 bg-white px-3 text-sm">
                    @foreach (__('marketplace_ui.sorts') as $value => $label)
                        <option value="{{ $value }}" @selected(request('sort', 'popular') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <label class="sr-only" for="catalogDelivery">{{ __('marketplace_ui.delivery') }}</label>
                <select id="catalogDelivery" name="delivery_time" onchange="this.form.submit()" class="h-9 rounded-full border border-black/10 bg-white px-3 text-sm">
                    @foreach (__('marketplace_ui.deliveries') as $value => $label)
                        <option value="{{ $value }}" @selected((string) request('delivery_time', '') === (string) $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </form>

        @if ($services->count() > 0)
            <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach ($services as $service)
                    @php
                        $url = route('marketplace.services.show', ['id' => $service->id, 'slug' => $service->slug ?? 'service']);
                        $minPrice = $service->packages->min('price') ?? $service->price ?? 0;
                        $firstPackage = $service->packages->first();
                        $symbol = $firstPackage && $firstPackage->currency ? ($firstPackage->currency->symbol ?? $firstPackage->currency->currency ?? '') : '';
                        $days = (int) ($service->packages->min('delivery_days') ?? $firstPackage->delivery_days ?? 0);
                        $serviceTitle = $service->localizedTitle();
                    @endphp
                    <article class="relative bg-white border border-black/5 rounded-[24px] shadow-sm hover:shadow-md transition-shadow p-6 flex flex-col gap-4">
                        <form action="{{ route('marketplace.favorites.toggle', $service->id) }}" method="POST" class="absolute top-4 end-4">
                            @csrf
                            <button type="submit" class="w-8 h-8 rounded-full flex items-center justify-center text-[#86868b] hover:text-rose-600 hover:bg-rose-50"
                                    title="{{ ! empty($service->is_favorited) ? __('marketplace_ui.saved') : __('marketplace_ui.save') }}"
                                    aria-label="{{ ! empty($service->is_favorited) ? __('marketplace_ui.saved') : __('marketplace_ui.save') }}">
                                <i class="{{ ! empty($service->is_favorited) ? 'ri-bookmark-fill text-rose-600' : 'ri-bookmark-line' }}" aria-hidden="true"></i>
                            </button>
                        </form>
                        <a href="{{ $url }}" class="flex flex-col gap-4 flex-1">
                            @if ($service->cover_image)
                                <img src="{{ $service->cover_image }}" alt="{{ $serviceTitle }}" class="w-full aspect-[16/9] object-cover rounded-2xl bg-[#f5f5f7]" loading="lazy">
                            @endif
                            <div class="flex-1">
                                @if ($service->category)<p class="text-xs text-[#1d1d1f]/50">{{ $service->category->localizedName() }}</p>@endif
                                <h2 class="mt-1 text-lg font-bold leading-snug line-clamp-2">{{ $serviceTitle }}</h2>
                                @if ($tagline = $service->localizedTagline())<p class="mt-2 text-sm text-[#1d1d1f]/60 line-clamp-2">{{ $tagline }}</p>@endif
                                @if ($days > 0)<p class="mt-2 text-xs text-[#1d1d1f]/50">{{ $days <= 1 ? __('marketplace_ui.one_day') : __('marketplace_ui.days', ['count' => $days]) }}</p>@endif
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-lg font-semibold"><span class="text-sm font-normal text-[#1d1d1f]/60">{{ __('marketplace_ui.from') }}</span> <span dir="ltr">{{ $symbol }}{{ number_format($minPrice, 0) }}</span></span>
                                <span class="text-sm font-semibold text-[#0071e3]">{{ __('marketplace_ui.details') }}</span>
                            </div>
                        </a>
                    </article>
                @endforeach
            </div>

            @if ($services->lastPage() > 1)
                <nav class="mt-10 flex items-center justify-between text-sm font-semibold" aria-label="{{ __('marketplace_ui.page', ['page' => $services->currentPage(), 'pages' => $services->lastPage()]) }}">
                    @if ($services->previousPageUrl())<a href="{{ $services->appends(request()->query())->previousPageUrl() }}" class="text-[#0071e3]">{{ __('marketplace_ui.previous') }}</a>@else<span></span>@endif
                    <span class="text-[#1d1d1f]/50">{{ __('marketplace_ui.page', ['page' => $services->currentPage(), 'pages' => $services->lastPage()]) }}</span>
                    @if ($services->nextPageUrl())<a href="{{ $services->appends(request()->query())->nextPageUrl() }}" class="text-[#0071e3]">{{ __('marketplace_ui.next') }}</a>@else<span></span>@endif
                </nav>
            @endif
        @else
            <div class="mt-6 rounded-[24px] bg-white border border-black/5 p-10 text-center">
                <p class="text-[#1d1d1f]/60">{{ __('marketplace_ui.empty') }}</p>
                <a href="{{ route('marketplace.services.index') }}" class="mt-4 inline-block text-sm font-semibold text-[#0071e3]">{{ __('marketplace_ui.show_all') }}</a>
            </div>
        @endif

        {{-- For developers: one quiet line instead of a dark ad card between the services --}}
        <div class="mt-12 rounded-[24px] bg-white border border-black/5 p-6 flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="font-bold">{{ __('marketplace_ui.sell_title') }}</p>
                <p class="mt-1 text-sm text-[#1d1d1f]/60">{{ __('marketplace_ui.sell_text') }}</p>
            </div>
            <a href="{{ route('marketplace.services.create') }}" class="inline-flex h-11 items-center rounded-full bg-[#0071e3] px-6 text-sm font-semibold text-white hover:bg-[#0077ed]">{{ __('marketplace_ui.sell_action') }}</a>
        </div>
    </div>
</div>
@endsection
