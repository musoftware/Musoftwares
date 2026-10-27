@extends('layouts.public')

@php
    $q = app()->getLocale() === 'ar' ? '?lang=ar' : '';
@endphp

@push('head')
    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => array_map(fn ($item) => ['@type' => 'Question', 'name' => $item['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['a']]], __('white_label.faq')),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endpush

@section('content')
<div class="w-full overflow-x-clip bg-[#fbfbfd] text-[#1d1d1f]">
    <section class="max-w-3xl mx-auto px-4 sm:px-6 pt-14 sm:pt-24 pb-14 text-center">
        <span class="inline-flex items-center gap-1.5 rounded-full bg-[#0071e3]/10 text-[#0071e3] px-3 py-1 text-xs font-semibold">
            <i class="ri-price-tag-3-line" aria-hidden="true"></i>{{ __('white_label.badge') }}
        </span>
        <h1 class="mt-5 text-3xl sm:text-5xl font-bold tracking-tight leading-tight">{{ __('white_label.headline') }}</h1>
        <p class="mt-5 text-base sm:text-lg text-[#1d1d1f]/60 leading-relaxed">{{ __('white_label.subtitle') }}</p>
        <p class="mt-6 text-2xl font-bold text-[#0071e3]">{{ __('white_label.price') }}</p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ route('white-label.settings') }}" class="inline-flex h-12 items-center rounded-full bg-[#0071e3] px-7 text-[15px] font-semibold text-white hover:bg-[#0077ed]">{{ __('white_label.start') }}</a>
            <a href="/services{{ $q }}#programs" class="inline-flex h-12 items-center rounded-full bg-white border border-black/10 px-6 text-[15px] font-semibold hover:bg-[#f5f5f7]">{{ __('white_label.see_programs') }}</a>
        </div>
    </section>

    <section class="max-w-[1100px] mx-auto px-4 sm:px-6 pb-16">
        <h2 class="text-2xl sm:text-3xl font-bold tracking-tight text-center">{{ __('white_label.steps_title') }}</h2>
        <ol class="mt-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach (__('white_label.steps') as $step)
                <li class="rounded-[24px] bg-white border border-black/5 p-6 shadow-sm">
                    <span class="flex w-9 h-9 items-center justify-center rounded-full bg-[#0071e3]/10 text-[#0071e3] font-bold">{{ $loop->iteration }}</span>
                    <h3 class="mt-4 font-bold">{{ $step['title'] }}</h3>
                    <p class="mt-1 text-sm text-[#1d1d1f]/60 leading-relaxed">{{ $step['text'] }}</p>
                </li>
            @endforeach
        </ol>
    </section>

    <section class="bg-white border-y border-black/5 py-16">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 text-center">
            <h2 class="text-2xl sm:text-3xl font-bold tracking-tight">{{ __('white_label.earn_title') }}</h2>
            <p class="mt-3 text-[#1d1d1f]/60 leading-relaxed">{{ __('white_label.earn_text') }}</p>
            <dl class="mt-8 mx-auto max-w-sm divide-y divide-black/5 rounded-[24px] bg-[#fbfbfd] border border-black/5 text-start">
                @foreach (__('white_label.earn_example') as $line)
                    <div class="flex items-center justify-between px-5 py-3 {{ $loop->last ? 'font-bold text-emerald-700' : '' }}">
                        <dt>{{ $line['label'] }}</dt><dd>{{ $line['value'] }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>

    <section class="max-w-[1100px] mx-auto px-4 sm:px-6 py-16">
        <h2 class="text-2xl sm:text-3xl font-bold tracking-tight text-center">{{ __('white_label.brand_title') }}</h2>
        <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-4">
            @foreach (__('white_label.brand_points') as $point)
                <div class="rounded-[24px] bg-white border border-black/5 p-6 shadow-sm">
                    <span class="flex w-11 h-11 items-center justify-center rounded-2xl bg-[#0071e3]/10 text-[#0071e3] text-xl"><i class="{{ $point['icon'] }}" aria-hidden="true"></i></span>
                    <h3 class="mt-4 font-bold">{{ $point['title'] }}</h3>
                    <p class="mt-1 text-sm text-[#1d1d1f]/60 leading-relaxed">{{ $point['text'] }}</p>
                </div>
            @endforeach
        </div>
        <p class="mt-6 text-center text-sm text-[#1d1d1f]/50">{{ __('white_label.payment_note') }}</p>
    </section>

    <section class="max-w-3xl mx-auto px-4 sm:px-6 pb-16">
        <h2 class="text-2xl sm:text-3xl font-bold tracking-tight text-center">{{ __('white_label.faq_title') }}</h2>
        <div class="mt-8 divide-y divide-black/5 rounded-[24px] bg-white border border-black/5">
            @foreach (__('white_label.faq') as $item)
                <details class="group p-5" @if ($loop->first) open @endif>
                    <summary class="cursor-pointer list-none font-semibold flex items-center justify-between gap-4 [&::-webkit-details-marker]:hidden">
                        {{ $item['q'] }}<i class="ri-add-line text-lg transition-transform group-open:rotate-45" aria-hidden="true"></i>
                    </summary>
                    <p class="mt-3 text-sm text-[#1d1d1f]/70 leading-relaxed">{{ $item['a'] }}</p>
                </details>
            @endforeach
        </div>
    </section>

    <section class="bg-[#1d1d1f] text-white py-16 text-center px-4">
        <h2 class="text-2xl sm:text-4xl font-bold tracking-tight">{{ __('white_label.headline') }}</h2>
        <p class="mt-3 text-white/70">{{ __('white_label.price') }}</p>
        <a href="{{ route('white-label.settings') }}" class="mt-8 inline-flex h-12 items-center rounded-full bg-white px-7 text-[15px] font-semibold text-[#1d1d1f] hover:bg-[#f5f5f7]">{{ __('white_label.start') }}</a>
    </section>
</div>
@endsection
