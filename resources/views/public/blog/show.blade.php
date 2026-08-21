@extends('layouts.public')

@section('content')
<div class="w-full bg-[#fbfbfd] text-[#1d1d1f] font-sans selection:bg-[#0071e3]/20 selection:text-[#0071e3] pt-16 sm:pt-24 pb-24 sm:pb-36">
    
    <div class="max-w-4xl mx-auto px-6 space-y-8">
        
        <!-- Back Navigation -->
        <a href="/blog" class="inline-flex items-center gap-2 text-xs font-semibold text-[#0071e3] hover:underline transition-all">
            &larr; Back to Technical Library
        </a>

        <!-- Header -->
        <div class="space-y-4">
            <span class="inline-block px-3.5 py-1 bg-[#0071e3]/10 border border-[#0071e3]/20 text-[#0071e3] text-xs font-semibold uppercase tracking-wider rounded-full">
                {{ $article->published_at ? $article->published_at->format('M d, Y') : 'Published' }} &bull; Technical Architecture
            </span>
            <h1 class="text-3xl sm:text-5xl font-bold text-[#1d1d1f] font-sans tracking-tight leading-tight">
                {{ $article->title }}
            </h1>
        </div>

        @if($article->featured_image)
            <div class="w-full h-80 sm:h-96 bg-zinc-100 rounded-[24px] border border-black/5 shadow-sm overflow-hidden">
                <img src="{{ $article->featured_image }}" alt="{{ $article->title }}" class="w-full h-full object-cover">
            </div>
        @endif

        <!-- Article Body -->
        <article class="prose max-w-none text-[#1d1d1f]/80 leading-relaxed space-y-6 pt-6 border-t border-black/5">
            {!! $article->content !!}
        </article>

        <!-- CTA Box -->
        <div class="mt-16 p-8 sm:p-12 bg-white border border-black/5 rounded-[24px] shadow-sm text-center space-y-6">
            <h3 class="text-2xl font-bold text-[#1d1d1f] font-sans">
                {{ __('landing_home.cta_title') }}
            </h3>
            <p class="text-sm text-[#1d1d1f]/60 max-w-xl mx-auto font-sans">
                {{ __('landing_home.cta_desc') }}
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 text-xs">
                <a href="/start-project" class="px-8 py-3.5 bg-[#0071e3] hover:bg-[#0077ed] text-white font-semibold uppercase tracking-wider rounded-[980px] shadow-sm transition-all">
                    {{ __('landing_home.cta_start_wizard') }}
                </a>
                <a href="https://wa.me/201015218548" target="_blank" rel="noopener noreferrer" class="px-8 py-3.5 border border-black/10 bg-[#f5f5f7] hover:bg-black/5 text-[#1d1d1f] font-semibold uppercase tracking-wider rounded-[980px] transition-all">
                    {{ __('landing_home.cta_whatsapp') }}
                </a>
            </div>
        </div>

    </div>

</div>
@endsection
