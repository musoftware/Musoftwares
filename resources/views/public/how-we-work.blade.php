@extends('layouts.public')

@php
    // One page for custom systems and how we work: what we build, the two ways to work with us,
    // how the price is set, and support after delivery. Text: lang/*/custom_systems.php and how_we_work.php.
    $q = app()->getLocale() === 'ar' ? '?lang=ar' : '';
    $faq = array_merge(
        array_slice(__('custom_systems.faq'), 0, 3),
        array_slice(__('how_we_work.faq'), 1)
    );
@endphp

@push('head')
    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'Service',
                'name' => __('custom_systems.title'),
                'description' => __('custom_systems.subtitle'),
                'provider' => ['@type' => 'Organization', 'name' => 'Musoftware', 'url' => url('/')],
                'areaServed' => 'Worldwide',
                'url' => route('how-we-work'),
            ],
            [
                '@type' => 'FAQPage',
                'mainEntity' => array_map(fn ($item) => ['@type' => 'Question', 'name' => $item['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['a']]], $faq),
            ],
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endpush

@section('content')
<div class="w-full overflow-x-clip bg-[#fbfbfd] text-[#1d1d1f]">
    {{-- 1. What we do --}}
    <section class="max-w-3xl mx-auto px-4 sm:px-6 pt-14 sm:pt-24 pb-14 text-center">
        <span class="inline-flex items-center gap-1.5 rounded-full bg-[#0071e3]/10 text-[#0071e3] px-3 py-1 text-xs font-semibold">
            <i class="ri-tools-line" aria-hidden="true"></i>{{ __('how_we_work.title') }}
        </span>
        <h1 class="mt-5 text-3xl sm:text-5xl font-bold tracking-tight leading-tight">{{ __('custom_systems.title') }}</h1>
        <p class="mt-5 text-base sm:text-lg text-[#1d1d1f]/60 leading-relaxed">{{ __('custom_systems.subtitle') }}</p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="/start-project{{ $q }}" class="inline-flex h-12 items-center rounded-full bg-[#0071e3] px-7 text-[15px] font-semibold text-white hover:bg-[#0077ed]">{{ __('custom_systems.start') }}</a>
            <a href="/estimator{{ $q }}" class="inline-flex h-12 items-center rounded-full bg-white border border-black/10 px-6 text-[15px] font-semibold hover:bg-[#f5f5f7]">{{ __('custom_systems.estimate') }}</a>
        </div>
    </section>

    {{-- 2. Two ways to work with us --}}
    <section class="max-w-[1100px] mx-auto px-4 sm:px-6 pb-16">
        <h2 class="text-2xl sm:text-3xl font-bold tracking-tight text-center">{{ __('how_we_work.paths_title') }}</h2>
        <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach ([
                ['title' => __('how_we_work.custom.title'), 'text' => __('how_we_work.custom.text'), 'steps' => array_map(fn ($s) => $s['title'].': '.$s['text'], __('custom_systems.steps')), 'href' => '/start-project'.$q, 'action' => __('how_we_work.custom.action'), 'icon' => 'ri-tools-line'],
                ['title' => __('how_we_work.ready.title'), 'text' => __('how_we_work.ready.text'), 'steps' => __('how_we_work.ready.steps'), 'href' => '/services'.$q.'#systems', 'action' => __('how_we_work.ready.action'), 'icon' => 'ri-shopping-bag-3-line'],
            ] as $path)
                <div class="bg-white border border-black/5 rounded-[24px] p-6 sm:p-8 shadow-sm flex flex-col">
                    <span class="flex w-12 h-12 items-center justify-center rounded-2xl bg-[#0071e3]/10 text-[#0071e3] text-2xl"><i class="{{ $path['icon'] }}" aria-hidden="true"></i></span>
                    <h3 class="mt-4 text-xl font-bold">{{ $path['title'] }}</h3>
                    <p class="mt-1 text-sm text-[#1d1d1f]/60">{{ $path['text'] }}</p>
                    <ol class="mt-5 space-y-3 flex-1">
                        @foreach ($path['steps'] as $step)
                            <li class="flex gap-3 text-[15px] leading-relaxed">
                                <span class="flex w-7 h-7 shrink-0 items-center justify-center rounded-full bg-[#f5f5f7] text-sm font-bold">{{ $loop->iteration }}</span>
                                <span class="pt-0.5">{{ $step }}</span>
                            </li>
                        @endforeach
                    </ol>
                    <a href="{{ $path['href'] }}" class="mt-6 inline-flex h-11 items-center justify-center rounded-full bg-[#0071e3] px-6 text-sm font-semibold text-white hover:bg-[#0077ed] self-start">{{ $path['action'] }}</a>
                </div>
            @endforeach
        </div>
    </section>

    {{-- 3. What we build --}}
    <section class="bg-white border-y border-black/5 py-16">
        <div class="max-w-[1100px] mx-auto px-4 sm:px-6">
            <h2 class="text-2xl sm:text-3xl font-bold tracking-tight text-center">{{ __('custom_systems.examples_title') }}</h2>
            <p class="mt-3 text-center text-[#1d1d1f]/60">{{ __('custom_systems.examples_hint') }}</p>
            <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach (__('custom_systems.examples') as $example)
                    <div class="rounded-[24px] bg-[#fbfbfd] border border-black/5 p-6">
                        <span class="flex w-11 h-11 items-center justify-center rounded-2xl bg-[#0071e3]/10 text-[#0071e3] text-xl"><i class="{{ $example['icon'] }}" aria-hidden="true"></i></span>
                        <h3 class="mt-4 font-bold">{{ $example['title'] }}</h3>
                        <p class="mt-2 text-sm text-[#1d1d1f]/60 leading-relaxed">{{ $example['text'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- 4. Price --}}
    <section class="max-w-3xl mx-auto px-4 sm:px-6 py-16 text-center">
        <span class="inline-flex w-12 h-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700 text-2xl"><i class="ri-calculator-line" aria-hidden="true"></i></span>
        <h2 class="mt-4 text-2xl sm:text-3xl font-bold tracking-tight">{{ __('how_we_work.pricing_title') }}</h2>
        <p class="mt-4 text-[#1d1d1f]/70 leading-relaxed">{{ __('how_we_work.pricing_text') }}</p>
        <a href="/estimator{{ $q }}" class="mt-6 inline-flex h-12 items-center rounded-full bg-[#0071e3] px-7 text-[15px] font-semibold text-white hover:bg-[#0077ed]">{{ __('how_we_work.pricing_action') }}</a>
    </section>

    {{-- 5. After delivery and support --}}
    <section class="bg-white border-y border-black/5 py-16">
        <div class="max-w-[1100px] mx-auto px-4 sm:px-6">
            <h2 class="text-2xl sm:text-3xl font-bold tracking-tight text-center">{{ __('how_we_work.after_title') }}</h2>
            <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach ([['free_month', 'ri-customer-service-2-line'], ['free_changes', 'ri-edit-2-line']] as [$key, $icon])
                    <div class="flex gap-4 rounded-[24px] bg-emerald-50/40 border border-emerald-200 p-6">
                        <span class="flex w-11 h-11 shrink-0 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700 text-xl"><i class="{{ $icon }}" aria-hidden="true"></i></span>
                        <div>
                            <h3 class="font-bold">{{ __("how_we_work.{$key}_title") }}</h3>
                            <p class="mt-1 text-sm text-[#1d1d1f]/60 leading-relaxed">{{ __("how_we_work.{$key}_text") }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            <h3 class="mt-12 text-xl sm:text-2xl font-bold tracking-tight text-center">{{ __('how_we_work.support_title') }}</h3>
            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach (['support_monthly', 'support_per_change'] as $key)
                    <div class="rounded-[24px] bg-[#fbfbfd] border border-black/5 p-6 sm:p-8">
                        <h4 class="text-lg font-bold">{{ __("how_we_work.{$key}_title") }}</h4>
                        <p class="mt-2 text-2xl font-bold text-[#0071e3]">{{ __("how_we_work.{$key}_price") }}</p>
                        <p class="mt-3 text-sm text-[#1d1d1f]/60 leading-relaxed">{{ __("how_we_work.{$key}_text") }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- 6. Why a system made for you --}}
    <section class="max-w-[1100px] mx-auto px-4 sm:px-6 py-16">
        <h2 class="text-2xl sm:text-3xl font-bold tracking-tight text-center">{{ __('custom_systems.why_title') }}</h2>
        <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 gap-4">
            @foreach (__('custom_systems.why') as $reason)
                <div class="flex gap-4 rounded-[24px] bg-white border border-black/5 p-6 shadow-sm">
                    <i class="ri-check-line text-2xl text-emerald-600" aria-hidden="true"></i>
                    <div>
                        <h3 class="font-bold">{{ $reason['title'] }}</h3>
                        <p class="mt-1 text-sm text-[#1d1d1f]/60 leading-relaxed">{{ $reason['text'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- 7. Questions --}}
    <section class="max-w-3xl mx-auto px-4 sm:px-6 pb-16">
        <h2 class="text-2xl sm:text-3xl font-bold tracking-tight text-center">{{ __('how_we_work.faq_title') }}</h2>
        <div class="mt-8 divide-y divide-black/5 rounded-[24px] bg-white border border-black/5">
            @foreach ($faq as $item)
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
        <h2 class="text-2xl sm:text-4xl font-bold tracking-tight">{{ __('custom_systems.cta_title') }}</h2>
        <p class="mt-3 text-white/70">{{ __('custom_systems.cta_text') }}</p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="/start-project{{ $q }}" class="inline-flex h-12 items-center rounded-full bg-white px-7 text-[15px] font-semibold text-[#1d1d1f] hover:bg-[#f5f5f7]">{{ __('custom_systems.start') }}</a>
            <a href="{{ \App\Support\SiteNav::WHATSAPP_URL }}" target="_blank" rel="noopener noreferrer" class="inline-flex h-12 items-center gap-2 rounded-full border border-white/30 px-7 text-[15px] font-semibold text-white hover:bg-white/10">
                <i class="ri-whatsapp-line" aria-hidden="true"></i>{{ __('custom_systems.whatsapp') }}
            </a>
        </div>
    </section>
</div>
@endsection
