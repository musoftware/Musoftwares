@extends('softwarestore::white-label.layout')

@section('content')
<div class="max-w-[1100px] mx-auto px-4 sm:px-6 {{ $embed ? 'py-6' : 'py-10 sm:py-14' }}">
    <h1 class="text-2xl sm:text-3xl font-bold tracking-tight">{{ __('white_label.programs') }}</h1>
    <p class="mt-1 text-[#1d1d1f]/60">{{ __('white_label.store_hint') }}</p>

    <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach ($products as ['product' => $product, 'price' => $price])
            <a href="{{ route('white-label.product', [$partner, $product->slug]) }}" @if ($embed) target="_blank" rel="noopener" @endif
               class="group flex flex-col rounded-[20px] bg-white border border-black/5 shadow-sm overflow-hidden hover:shadow-md transition-shadow">
                @if ($product->imageUrl())
                    <img src="{{ $product->imageUrl() }}" alt="" loading="lazy" class="aspect-video w-full object-cover bg-[#f5f5f7]">
                @endif
                <div class="flex flex-1 flex-col p-5">
                    <h2 class="font-bold">{{ $product->localized('name') }}</h2>
                    @if ($product->localized('tagline'))
                        <p class="mt-1 text-sm text-[#1d1d1f]/60 leading-relaxed line-clamp-3">{{ $product->localized('tagline') }}</p>
                    @endif
                    <div class="mt-auto pt-4 flex items-center justify-between gap-3">
                        <span class="font-bold">
                            @if ($product->hasPlans()){{ trim(__('white_label.from', ['price' => ''])) }} @endif<span dir="ltr">{{ $product->money($price) }} {{ $product->saleCurrency() }}</span>
                        </span>
                        <span class="inline-flex h-9 items-center rounded-full bg-[var(--wl-accent)] px-4 text-sm font-semibold text-white">{{ __('white_label.buy') }}</span>
                    </div>
                </div>
            </a>
        @endforeach
    </div>
</div>
@endsection
