@extends('layouts.public')

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
                'url' => route('custom-systems'),
            ],
            [
                '@type' => 'FAQPage',
                'mainEntity' => array_map(fn ($item) => ['@type' => 'Question', 'name' => $item['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['a']]], __('custom_systems.faq')),
            ],
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endpush

{{-- Replaces the old CRM/ERP "platforms" pages: we learn how the business works, then build its system. --}}
@section('content')
@php $ar = app()->getLocale() === 'ar'; $q = $ar ? '?lang=ar' : ''; @endphp
<div class="w-full overflow-x-clip bg-[#fbfbfd] text-[#1d1d1f]">
    <section class="max-w-3xl mx-auto px-4 sm:px-6 pt-14 sm:pt-24 pb-14 text-center">
        <span class="inline-flex items-center gap-1.5 rounded-full bg-[#0071e3]/10 text-[#0071e3] px-3 py-1 text-xs font-semibold">
            <i class="ri-tools-line" aria-hidden="true"></i>{{ __('custom_systems.badge') }}
        </span>
        <h1 class="mt-5 text-3xl sm:text-5xl font-bold tracking-tight leading-tight">{{ __('custom_systems.title') }}</h1>
        <p class="mt-5 text-base sm:text-lg text-[#1d1d1f]/60 leading-relaxed">{{ __('custom_systems.subtitle') }}</p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="/start-project{{ $q }}" class="inline-flex h-12 items-center rounded-full bg-[#0071e3] px-7 text-[15px] font-semibold text-white hover:bg-[#0077ed]">{{ __('custom_systems.start') }}</a>
            <a href="{{ \App\Support\SiteNav::WHATSAPP_URL }}" target="_blank" rel="noopener noreferrer" class="inline-flex h-12 items-center gap-2 rounded-full bg-white border border-black/10 px-6 text-[15px] font-semibold hover:bg-[#f5f5f7]">
                <i class="ri-whatsapp-line text-[#25D366]" aria-hidden="true"></i>{{ __('custom_systems.whatsapp') }}
            </a>
        </div>
    </section>

    <section class="max-w-[1100px] mx-auto px-4 sm:px-6 pb-16">
        <h2 class="text-2xl sm:text-3xl font-bold tracking-tight text-center">{{ __('custom_systems.how_title') }}</h2>
        <ol class="mt-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach (__('custom_systems.steps') as $step)
                <li class="bg-white border border-black/5 rounded-[24px] p-6 shadow-sm">
                    <span class="flex w-9 h-9 items-center justify-center rounded-full bg-[#0071e3] text-white font-bold">{{ $loop->iteration }}</span>
                    <h3 class="mt-4 text-lg font-bold">{{ $step['title'] }}</h3>
                    <p class="mt-2 text-sm text-[#1d1d1f]/60 leading-relaxed">{{ $step['text'] }}</p>
                </li>
            @endforeach
        </ol>
    </section>

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

        <div class="mt-10 rounded-[24px] bg-white border border-black/5 p-6 flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="font-bold">{{ __('custom_systems.ready_title') }}</p>
                <p class="mt-1 text-sm text-[#1d1d1f]/60">{{ __('custom_systems.ready_text') }}</p>
            </div>
            <a href="/services{{ $q }}#systems" class="text-sm font-semibold text-[#0071e3]">{{ __('custom_systems.ready_link') }}</a>
        </div>
    </section>

    <section class="max-w-3xl mx-auto px-4 sm:px-6 pb-16">
        <h2 class="text-2xl sm:text-3xl font-bold tracking-tight text-center">{{ __('custom_systems.faq_title') }}</h2>
        <div class="mt-8 divide-y divide-black/5 rounded-[24px] bg-white border border-black/5">
            @foreach (__('custom_systems.faq') as $item)
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
            <a href="/estimator{{ $q }}" class="inline-flex h-12 items-center rounded-full border border-white/30 px-7 text-[15px] font-semibold text-white hover:bg-white/10">{{ __('custom_systems.estimate') }}</a>
        </div>
    </section>
</div>
@endsection
