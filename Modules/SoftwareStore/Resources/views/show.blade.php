@extends('layouts.public', [
    'title' => $product->localized('name').' | Musoftwares',
    'description' => app(\Modules\SoftwareStore\Services\StoreSeo::class)->description($product),
    'canonical' => $product->purchaseUrl(),
    'image' => app(\Modules\SoftwareStore\Services\StoreSeo::class)->ogImage($product),
])

@include('softwarestore::landing.seo')

@php
    $copy = $product->landingCopy();
    $colors = $product->themeColors();
    $gallery = $product->galleryUrls();
    $inquiryLink = 'https://wa.me/'.app(\Modules\SoftwareStore\Services\SoftwareOrderService::class)->ownerWhatsappNumber($product)
        .'?text='.rawurlencode('Hello, I have a question about '.$product->name_en);
@endphp

@section('content')
@include('softwarestore::landing.language')
<div class="w-full overflow-x-clip bg-[#fafbfc] text-[#111827]"
     style="--sw-accent: {{ $colors['accent'] }}; --sw-ink: {{ $colors['ink'] }};">

    {{-- Hero: dark, one message, one action --}}
    <section class="bg-[var(--sw-ink)] text-white">
        <div class="max-w-[1100px] mx-auto px-4 sm:px-6 py-16 sm:py-24 grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
            <div class="space-y-6 min-w-0">
                <p class="text-sm font-semibold text-[var(--sw-accent)]">{{ $copy['kicker'] ?? $product->localized('tagline') }}</p>
                <h1 class="text-4xl sm:text-5xl font-extrabold leading-tight">{{ $copy['headline'] ?? $product->localized('name') }}</h1>
                <p class="text-lg text-white/70 leading-relaxed">{{ $copy['subheadline'] ?? $product->localized('tagline') }}</p>
                <div class="h-1 w-24 rounded bg-[var(--sw-accent)]"></div>
                <div class="flex flex-col sm:flex-row gap-3 pt-2">
                    <a href="#order" class="h-12 px-8 inline-flex items-center justify-center rounded-xl bg-[var(--sw-accent)] text-white font-semibold">
                        {{ __('software.buy') }}
                    </a>
                    <a href="{{ $inquiryLink }}" target="_blank" rel="noopener noreferrer"
                       class="h-12 px-8 inline-flex items-center justify-center rounded-xl border border-white/25 text-white font-semibold hover:bg-white/10">
                        {{ __('software.ask_whatsapp') }}
                    </a>
                </div>
            </div>
            <div class="min-w-0">
                @if ($gallery)
                    <img src="{{ $gallery[0] }}" alt="{{ $product->localized('name') }}" class="w-full rounded-2xl border border-white/10 shadow-2xl">
                @else
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-10 flex items-center justify-center">
                        <img src="{{ $product->imageUrl() }}" alt="{{ $product->localized('name') }}" class="w-40 h-40 object-contain">
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- Body: each program picks its own section order --}}
    @foreach ($product->sectionOrder() as $section)
        @includeIf('softwarestore::landing.sections.'.$section)
    @endforeach

    {{-- Final call to action with the order form --}}
    @include('softwarestore::landing.order')
</div>
@endsection
