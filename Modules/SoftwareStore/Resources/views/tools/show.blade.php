@php
    $seo = app(\Modules\SoftwareStore\Services\StoreSeo::class);
    $toolUrl = \Modules\SoftwareStore\Services\FreeTools::url($slug);
@endphp
@extends('layouts.public', [
    'title' => $copy['title'].' | Musoftware',
    'description' => $copy['description'],
    'canonical' => \App\Support\LocaleUrl::canonical($toolUrl),
])

@push('head')
    <meta name="robots" content="index,follow,max-image-preview:large">
    @foreach ($seo->alternates($toolUrl) as $lang => $href)
        <link rel="alternate" hreflang="{{ $lang }}" href="{{ $href }}">
    @endforeach
    <script type="application/ld+json">{!! $seo->toJson($seo->toolGraph($slug)) !!}</script>
    <style>
        .ft-label { display: block; font-size: 13px; font-weight: 600; color: #1d1d1f; margin-bottom: 6px; }
        .ft-input { width: 100%; border: 1px solid rgba(0,0,0,.12); border-radius: 12px; padding: 10px 12px; font-size: 15px; background: #fff; color: #1d1d1f; }
        .ft-input:focus { outline: none; border-color: #0071e3; box-shadow: 0 0 0 3px rgba(0,113,227,.15); }
        .ft-btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; border-radius: 12px; padding: 10px 18px; font-size: 14px; font-weight: 600; background: #0071e3; color: #fff; transition: background .2s; }
        .ft-btn:hover { background: #0077ed; }
        .ft-btn-light { background: #f5f5f7; color: #1d1d1f; }
        .ft-btn-light:hover { background: #e8e8ed; }
        .ft-stat { background: #f5f5f7; border-radius: 16px; padding: 14px 16px; }
        .ft-stat dt { font-size: 12px; color: rgba(29,29,31,.6); }
        .ft-stat dd { margin-top: 4px; font-size: 20px; font-weight: 700; color: #1d1d1f; direction: ltr; unicode-bidi: plaintext; }
    </style>
@endpush

@section('content')
@include('softwarestore::landing.language')
<script>
    // Small helpers shared by every free tool on this page.
    window.FT = {
        number(value, digits = 2) {
            return Number.isFinite(value) ? value.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: digits }) : '—';
        },
        parse(value) {
            const digits = String(value ?? '').replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d)).replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d)).replace(/[,٬\s]/g, '').replace('٫', '.');
            const number = parseFloat(digits);
            return Number.isFinite(number) ? number : 0;
        },
        async copy(text, button) {
            try { await navigator.clipboard.writeText(text); } catch (e) {
                const area = document.createElement('textarea'); area.value = text; document.body.appendChild(area); area.select(); document.execCommand('copy'); area.remove();
            }
            if (button) { const old = button.textContent; button.textContent = @json(__('free_tools.ui.copied')); setTimeout(() => button.textContent = old, 1500); }
        },
        download(name, content, type) {
            const blob = content instanceof Blob ? content : new Blob([content], { type });
            const link = document.createElement('a'); link.href = URL.createObjectURL(blob); link.download = name; link.click();
            setTimeout(() => URL.revokeObjectURL(link.href), 1000);
        },
        qr(canvas, text, size = 280) {
            const code = qrcode(0, 'M'); code.addData(text); code.make();
            const count = code.getModuleCount(), cell = Math.max(2, Math.floor(size / (count + 8))), full = cell * (count + 8);
            canvas.width = canvas.height = full;
            const ctx = canvas.getContext('2d'); ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, full, full); ctx.fillStyle = '#000';
            for (let r = 0; r < count; r++) for (let c = 0; c < count; c++) if (code.isDark(r, c)) ctx.fillRect((c + 4) * cell, (r + 4) * cell, cell, cell);
        },
    };
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcode-generator/1.4.4/qrcode.min.js" defer></script>

<div class="w-full overflow-x-clip bg-[#fbfbfd] text-[#1d1d1f] pt-10 sm:pt-16 pb-20">
    <div class="max-w-[960px] mx-auto px-4 sm:px-6">
        <nav class="text-[13px] text-[#1d1d1f]/60 flex flex-wrap items-center gap-1.5" aria-label="breadcrumb">
            <a href="{{ route('services.index') }}#tools" class="hover:text-[#0071e3]">{{ __('software.title') }}</a>
            <span aria-hidden="true">›</span>
            <span class="text-[#1d1d1f]">{{ $copy['name'] }}</span>
        </nav>

        <header class="mt-6 text-center">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-[#0071e3]/10 text-[#0071e3] px-3 py-1 text-xs font-semibold">
                <i class="{{ \Modules\SoftwareStore\Services\FreeTools::icon($slug) }}" aria-hidden="true"></i>{{ __('free_tools.ui.free_badge') }}
            </span>
            <h1 class="mt-4 text-3xl sm:text-5xl font-bold tracking-tight">{{ $copy['name'] }}</h1>
            <p class="mt-4 text-base sm:text-lg text-[#1d1d1f]/60 max-w-2xl mx-auto leading-relaxed">{{ $copy['intro'] }}</p>
        </header>

        <section class="mt-10 bg-white border border-black/5 rounded-[24px] shadow-sm p-5 sm:p-8" id="tool">
            @include('softwarestore::tools.partials.'.$slug)
        </section>
        <p class="mt-3 text-center text-xs text-[#1d1d1f]/50"><i class="ri-lock-line" aria-hidden="true"></i> {{ __($slug === 'qr-menu-maker' ? 'free_tools.ui.privacy_menu' : 'free_tools.ui.privacy') }}</p>

        <section class="mt-14">
            <h2 class="text-2xl font-bold tracking-tight">{{ __('free_tools.ui.how_title') }}</h2>
            <ol class="mt-5 grid grid-cols-1 sm:grid-cols-3 gap-4">
                @foreach ($copy['steps'] as $step)
                    <li class="bg-white border border-black/5 rounded-[20px] p-5 text-sm leading-relaxed">
                        <span class="flex w-8 h-8 items-center justify-center rounded-full bg-[#0071e3] text-white font-bold mb-3">{{ $loop->iteration }}</span>
                        {{ $step }}
                    </li>
                @endforeach
            </ol>
        </section>

        @if ($related->isNotEmpty())
            <section class="mt-14">
                <h2 class="text-2xl font-bold tracking-tight">{{ __('free_tools.ui.related_title') }}</h2>
                <p class="mt-2 text-sm text-[#1d1d1f]/60">{{ __('free_tools.ui.related_hint') }}</p>
                <div class="mt-5 grid grid-cols-1 sm:grid-cols-3 gap-4">
                    @foreach ($related as $product)
                        <a href="{{ $product->purchaseUrl() }}" class="bg-white border border-black/5 rounded-[20px] p-5 hover:shadow-md transition-shadow flex flex-col gap-3">
                            @if ($product->imageUrl())
                                <img src="{{ $product->imageUrl() }}" alt="{{ $product->localized('name') }}" class="w-12 h-12 object-contain" loading="lazy">
                            @endif
                            <span class="font-bold">{{ $product->localized('name') }}</span>
                            <span class="text-sm text-[#1d1d1f]/60 flex-1">{{ $product->localized('tagline') }}</span>
                            <span class="text-sm font-semibold text-[#0071e3]">{{ __('free_tools.ui.details') }}</span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="mt-14">
            <h2 class="text-2xl font-bold tracking-tight">{{ __('free_tools.ui.faq_title') }}</h2>
            <div class="mt-5 divide-y divide-black/5 bg-white border border-black/5 rounded-[20px]">
                @foreach ($copy['faq'] as $item)
                    <details class="group p-5" @if ($loop->first) open @endif>
                        <summary class="cursor-pointer list-none font-semibold flex items-center justify-between gap-4 [&::-webkit-details-marker]:hidden">
                            {{ $item['q'] }}
                            <i class="ri-add-line text-lg transition-transform group-open:rotate-45" aria-hidden="true"></i>
                        </summary>
                        <p class="mt-3 text-sm text-[#1d1d1f]/70 leading-relaxed">{{ $item['a'] }}</p>
                    </details>
                @endforeach
            </div>
        </section>

        <section class="mt-14">
            <h2 class="text-2xl font-bold tracking-tight">{{ __('free_tools.ui.other_tools') }}</h2>
            @include('softwarestore::tools.grid', ['except' => $slug])
        </section>

        <div class="mt-12 text-center">
            <a href="{{ \App\Support\SiteNav::WHATSAPP_URL }}" target="_blank" rel="noopener noreferrer" class="ft-btn" style="background:#25D366">
                <i class="ri-whatsapp-line" aria-hidden="true"></i>{{ __('free_tools.ui.whatsapp_cta') }}
            </a>
        </div>
    </div>
</div>
@endsection
