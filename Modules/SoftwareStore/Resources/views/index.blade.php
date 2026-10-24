@extends('layouts.public', ['title' => __('software.title').' | Musoftwares', 'description' => __('software.meta_description'), 'canonical' => route('services.index')])

@include('softwarestore::landing.seo-index', ['products' => $products])

@section('content')
@include('softwarestore::landing.language')
<div class="w-full overflow-x-clip bg-[#fbfbfd] text-[#1d1d1f] pt-16 sm:pt-24 pb-24">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 text-center mb-12">
        <h1 class="text-3xl sm:text-5xl font-bold tracking-tight">{{ __('software.title') }}</h1>
        <p class="mt-4 text-base text-[#1d1d1f]/60">{{ __('software.subtitle') }}</p>
    </div>

    @if ($groups->count() > 1)
        <nav class="max-w-[1100px] mx-auto px-4 sm:px-6 mb-10 flex flex-wrap justify-center gap-x-8 gap-y-2 text-sm font-semibold" aria-label="{{ __('software.title') }}">
            @foreach ($groups as $key => $items)
                <a href="#{{ $key }}" class="text-[#0071e3] hover:underline">{{ __('software.groups.'.$key) }} ({{ $items->count() }})</a>
            @endforeach
        </nav>
    @endif

    @forelse ($groups as $key => $items)
        <section id="{{ $key }}" class="max-w-[1100px] mx-auto px-4 sm:px-6 {{ $loop->first ? '' : 'mt-16' }} scroll-mt-24">
            <h2 class="text-2xl sm:text-3xl font-bold tracking-tight">{{ __('software.groups.'.$key) }}</h2>
            <p class="mt-2 mb-6 text-sm text-[#1d1d1f]/60">{{ __('software.group_hint.'.$key) }}</p>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach ($items as $product)
                    <a href="{{ route('website-services.show', $product->slug) }}"
                       class="bg-white border border-black/5 rounded-[24px] shadow-sm hover:shadow-md transition-shadow p-6 flex flex-col gap-4">
                        <img src="{{ $product->imageUrl() }}" alt="{{ $product->localized('name') }}"
                             class="w-20 h-20 object-contain" loading="lazy">
                        <div class="flex-1">
                            <h3 class="text-xl font-bold">{{ $product->localized('name') }}</h3>
                            <p class="mt-2 text-sm text-[#1d1d1f]/60">{{ $product->localized('tagline') }}</p>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-lg font-semibold">@if ($product->hasPlans())<span class="text-sm font-normal text-[#1d1d1f]/60">{{ __('software.from') }}</span> @endif{{ $product->money($product->startingPrice()) }} {{ $product->saleCurrency() }} <span class="text-sm font-normal text-[#1d1d1f]/60">{{ $product->approxUsdLabel($product->startingPrice()) }} {{ $product->periodLabel() }}</span></span>
                            <span class="text-sm font-semibold text-[#0071e3]">{{ __('software.details') }}</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @empty
        <p class="text-center text-[#1d1d1f]/60">{{ __('software.empty') }}</p>
    @endforelse
</div>
@endsection
