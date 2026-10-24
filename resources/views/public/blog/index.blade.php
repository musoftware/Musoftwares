@extends('layouts.public')

@php
    $isAr = app()->getLocale() === 'ar';
    $t = fn (string $ar, string $en) => $isAr ? $ar : $en;
@endphp

@push('head')
    <meta name="robots" content="{{ request()->filled('search') || request()->integer('page') > 1 ? 'noindex,follow' : 'index,follow,max-image-preview:large' }}">
@endpush

@section('content')
<div class="w-full overflow-x-clip bg-white text-[#1d1d1f] pt-14 sm:pt-20 pb-20">

    <div class="max-w-[820px] mx-auto px-4 sm:px-6 text-center">
        <h1 class="text-[34px] sm:text-[46px] font-bold tracking-tight leading-[1.25]">{{ $t('المدونة', 'Blog') }}</h1>
        <p class="mt-4 text-[17px] leading-[1.8] text-[#515154]">{{ $t('مقالات عملية قبل ما تشتري برنامج أو تبني موقع.', 'Practical guides before you buy software or build a website.') }}</p>
    </div>

    <form method="GET" action="{{ route('blog.index') }}" class="max-w-[560px] mx-auto px-4 sm:px-6 mt-8 flex gap-2" role="search">
        <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ $t('ابحث في المقالات', 'Search the articles') }}"
               class="flex-1 min-w-0 rounded-lg border border-black/15 bg-white px-4 py-3 text-[15px] focus:outline-none focus:border-[#0071e3]">
        <button type="submit" style="background-color: #0071e3; color: #ffffff;" class="shrink-0 rounded-lg px-6 py-3 text-[15px] font-semibold hover:bg-[#0077ed] transition-colors">{{ $t('بحث', 'Search') }}</button>
    </form>

    <div class="max-w-[1100px] mx-auto px-4 sm:px-6 mt-12">
        @if ($articles->isEmpty())
            <p class="py-16 text-center text-[15px] text-[#86868b]">{{ $t('مفيش مقالات مطابقة.', 'No matching articles.') }}</p>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($articles as $article)
                    <a href="{{ route('blog.show', $article->slug) }}" class="rounded-2xl border border-black/10 p-6 hover:shadow-md transition-shadow flex flex-col">
                        <p class="text-[13px] text-[#86868b]">{{ optional($article->published_at)->translatedFormat('j F Y') }}</p>
                        <h2 class="mt-2 text-[20px] font-bold leading-snug">{{ $article->title }}</h2>
                        <p class="mt-3 text-[15px] leading-[1.8] text-[#515154] flex-1 line-clamp-3">{{ $article->excerpt }}</p>
                        <span class="mt-5 text-[14px] font-semibold text-[#0071e3]">{{ $t('اقرا المقال', 'Read the article') }}</span>
                    </a>
                @endforeach
            </div>
            @if ($articles->hasPages())
                <div class="mt-12 flex justify-center">{{ $articles->links() }}</div>
            @endif
        @endif
    </div>
</div>
@endsection
