@extends('layouts.public')

@php
    $isAr = app()->getLocale() === 'ar';
    $t = fn (string $ar, string $en) => $isAr ? $ar : $en;
    $whatsapp = 'https://wa.me/201015218548?text='.rawurlencode($t('مرحبا، قريت مقال على موقعكم وعايز أسأل', 'Hello, I read an article on your site and have a question'));
@endphp

@push('head')
    @foreach ($alternates as $language => $href)
        <link rel="alternate" hreflang="{{ $language }}" href="{{ $href }}">
    @endforeach
    @if ($alternates->isNotEmpty())
        <link rel="alternate" hreflang="x-default" href="{{ $alternates->get('en', $alternates->first()) }}">
    @endif
    <meta property="og:type" content="article">
    <script type="application/ld+json">{!! $articleJson !!}</script>
@endpush

@section('content')
<div class="w-full overflow-x-clip bg-white text-[#1d1d1f] pt-12 sm:pt-16 pb-20">
    <div class="max-w-[760px] mx-auto px-4 sm:px-6">

        <nav aria-label="breadcrumb" class="text-[14px] text-[#86868b]">
            <a href="{{ route('blog.index') }}" class="hover:text-[#0071e3]">{{ $t('المدونة', 'Blog') }}</a>
        </nav>

        <header class="mt-6">
            <h1 class="text-[32px] sm:text-[42px] font-bold tracking-tight leading-[1.3]">{{ $article->title }}</h1>
            <p class="mt-4 text-[14px] text-[#86868b]">
                {{ optional($article->published_at)->translatedFormat('j F Y') }}
                &middot; {{ $t('بقلم محمود أمين', 'By Mahmoud Amin') }}
                &middot; {{ $readingMinutes }} {{ $t('دقيقة قراءة', 'min read') }}
            </p>
        </header>

        @if ($article->featured_image)
            <img src="{{ $article->featured_image }}" alt="{{ $article->title }}" class="mt-8 w-full rounded-2xl border border-black/10 object-cover aspect-[16/9]">
        @endif

        <article class="mt-10 text-[17px] leading-[2] text-[#2d2d2f]
            [&_h2]:mt-12 [&_h2]:mb-4 [&_h2]:text-[26px] [&_h2]:font-bold [&_h2]:leading-snug [&_h2]:text-[#1d1d1f]
            [&_h3]:mt-8 [&_h3]:mb-3 [&_h3]:text-[20px] [&_h3]:font-bold [&_h3]:text-[#1d1d1f]
            [&_p]:mt-4 [&_ul]:mt-4 [&_ul]:list-disc [&_ul]:ps-6 [&_ol]:mt-4 [&_ol]:list-decimal [&_ol]:ps-6 [&_li]:mt-2
            [&_a]:text-[#0071e3] [&_a]:underline [&_strong]:font-semibold
            [&_table]:mt-6 [&_table]:w-full [&_table]:border-collapse [&_th]:border [&_th]:border-black/15 [&_th]:bg-[#f5f5f7] [&_th]:p-3 [&_th]:text-start [&_td]:border [&_td]:border-black/15 [&_td]:p-3 [&_blockquote]:mt-6 [&_blockquote]:border-s-4 [&_blockquote]:border-[#0071e3] [&_blockquote]:ps-4">
            {!! \App\Support\HtmlSanitizer::clean($article->content) !!}
        </article>

        <aside class="mt-16 rounded-2xl bg-[#f5f5f7] p-8 text-center">
            <h2 class="text-[22px] font-bold">{{ $t('عايز تشوف المنتجات والأسعار؟', 'Want to see the products and prices?') }}</h2>
            <div class="mt-5 flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ route('services.index') }}" style="background-color: #0071e3; color: #ffffff;" class="inline-flex items-center justify-center px-6 py-3 rounded-lg text-[15px] font-semibold hover:bg-[#0077ed] transition-colors">{{ $t('تصفح المنتجات', 'Browse products') }}</a>
                <a href="{{ $whatsapp }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center px-6 py-3 rounded-lg border border-black/15 bg-white text-[15px] font-semibold hover:bg-[#fbfbfd] transition-colors">{{ $t('اسأل على واتساب', 'Ask on WhatsApp') }}</a>
            </div>
        </aside>
    </div>
</div>
@endsection
