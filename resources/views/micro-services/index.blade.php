@extends('layouts.public', ['title' => __('micro_services.title').__('seo.suffix'), 'description' => __('micro_services.meta_description'), 'canonical' => $canonical])

@push('head')
    <meta name="robots" content="index,follow,max-image-preview:large">
    <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endpush

@section('content')
<div class="w-full overflow-x-clip bg-[#fbfbfd] text-[#1d1d1f] pt-16 sm:pt-24 pb-24">
    <header class="max-w-3xl mx-auto px-4 sm:px-6 text-center mb-12">
        <h1 class="text-3xl sm:text-5xl font-bold tracking-tight">{{ __('micro_services.title') }}</h1>
        <p class="mt-4 text-base text-[#1d1d1f]/60">{{ __('micro_services.subtitle') }}</p>
        @if ($hasOrders)
            <a href="{{ route('micro-services.mine') }}" class="mt-4 inline-block text-sm font-semibold text-[#0071e3] hover:underline">{{ __('micro_services.your_orders') }}</a>
        @endif
    </header>

    <div class="max-w-3xl mx-auto px-4 sm:px-6">
        @forelse ($services as $service)
            <article id="service-{{ $service->id }}" class="border-t border-black/10 py-8 scroll-mt-24">
                <h2 class="text-xl sm:text-2xl font-bold">{{ $service->title }}</h2>
                <p class="mt-2 text-[#1d1d1f]/70 leading-relaxed">{{ $service->description }}</p>
                @if ($service->example_url)
                    <a href="{{ $service->example_url }}" target="_blank" rel="noopener nofollow" class="mt-3 inline-flex min-h-11 items-center text-sm font-semibold text-[#0071e3] hover:underline">{{ __('micro_services.example') }}</a>
                @endif
            </article>
        @empty
            <p class="text-center text-[#1d1d1f]/60">{{ __('micro_services.empty') }}</p>
        @endforelse
    </div>

    <section class="max-w-3xl mx-auto px-4 sm:px-6 mt-12" aria-labelledby="start-title">
        <div class="rounded-[24px] bg-white border border-black/5 p-6 sm:p-8">
            <h2 id="start-title" class="text-xl font-bold">{{ __('micro_services.start_title') }}</h2>
            <p class="mt-2 text-sm text-[#1d1d1f]/60">{{ __('micro_services.start_hint') }}</p>
            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('builder.index') }}" class="inline-flex min-h-11 items-center rounded-full bg-[#1d1d1f] px-6 text-sm font-semibold text-white hover:bg-black">{{ __('micro_services.ai') }}</a>
                <a href="{{ route('company.contact') }}" class="inline-flex min-h-11 items-center rounded-full border border-black/15 px-6 text-sm font-semibold hover:border-black">{{ __('micro_services.contact') }}</a>
            </div>
            <p class="mt-4 text-sm text-[#1d1d1f]/60">{{ __('micro_services.ai_note') }}</p>
        </div>
    </section>
</div>
@endsection
