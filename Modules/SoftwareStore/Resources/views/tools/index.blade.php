@php
    $seo = app(\Modules\SoftwareStore\Services\StoreSeo::class);
    $hubUrl = route('services.tools.index');
@endphp
@extends('layouts.public', [
    'title' => __('free_tools.hub.title').' | Musoftware',
    'description' => __('free_tools.hub.description'),
    'canonical' => \App\Support\LocaleUrl::canonical($hubUrl),
])

@push('head')
    <meta name="robots" content="index,follow,max-image-preview:large">
    @foreach ($seo->alternates($hubUrl) as $lang => $href)
        <link rel="alternate" hreflang="{{ $lang }}" href="{{ $href }}">
    @endforeach
    <script type="application/ld+json">{!! $seo->toJson($seo->toolsHubGraph()) !!}</script>
@endpush

@section('content')
<div class="w-full overflow-x-clip bg-[#fbfbfd] text-[#1d1d1f] pt-10 sm:pt-16 pb-20">
    <div class="max-w-[1100px] mx-auto px-4 sm:px-6">
        <header class="text-center">
            <h1 class="text-3xl sm:text-5xl font-bold tracking-tight">{{ __('free_tools.hub.heading') }}</h1>
            <p class="mt-4 text-base sm:text-lg text-[#1d1d1f]/60 max-w-2xl mx-auto leading-relaxed">{{ __('free_tools.hub.intro') }}</p>
        </header>

        @include('softwarestore::tools.grid')

        <section class="mt-14 max-w-3xl mx-auto">
            <h2 class="text-2xl font-bold tracking-tight">{{ __('free_tools.hub.why_title') }}</h2>
            @foreach (__('free_tools.hub.why') as $paragraph)
                <p class="mt-3 text-[15px] text-[#1d1d1f]/70 leading-[1.9]">{{ $paragraph }}</p>
            @endforeach
        </section>
    </div>
</div>
@endsection
