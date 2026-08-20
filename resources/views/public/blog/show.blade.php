@extends('layouts.public')

@section('content')
<div class="w-full bg-[#111111] text-[#E5E5E5] font-sans selection:bg-[#748660] selection:text-white pt-16 sm:pt-24 pb-24 sm:pb-36">
    
    <div class="max-w-4xl mx-auto px-6 space-y-8">
        
        <!-- Back Navigation -->
        <a href="/blog" class="inline-flex items-center gap-2 text-xs font-mono text-zinc-400 hover:text-white transition-colors">
            &larr; Back to Technical Library
        </a>

        <!-- Header -->
        <div class="space-y-4">
            <span class="inline-block px-3 py-1 bg-[#1E2619] border border-[#748660]/40 text-[#748660] text-xs font-mono font-bold uppercase tracking-wider">
                {{ $article->published_at ? $article->published_at->format('M d, Y') : 'Published' }} &bull; Technical Architecture
            </span>
            <h1 class="text-3xl sm:text-5xl font-bold text-white font-sans tracking-tight leading-tight">
                {{ $article->title }}
            </h1>
        </div>

        @if($article->featured_image)
            <div class="w-full h-80 sm:h-96 bg-zinc-900 border border-[#262626] overflow-hidden">
                <img src="{{ $article->featured_image }}" alt="{{ $article->title }}" class="w-full h-full object-cover">
            </div>
        @endif

        <!-- Article Body -->
        <article class="prose prose-invert max-w-none text-zinc-300 leading-relaxed space-y-6 pt-6 border-t border-[#222222]">
            {!! $article->content !!}
        </article>

        <!-- CTA Box -->
        <div class="mt-16 p-8 sm:p-12 bg-[#161616] border border-[#262626] text-center space-y-6">
            <h3 class="text-2xl font-bold text-white font-sans">
                {{ __('landing_home.cta_title') }}
            </h3>
            <p class="text-sm text-zinc-400 max-w-xl mx-auto font-sans">
                {{ __('landing_home.cta_desc') }}
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 font-mono text-xs">
                <a href="/start-project" class="px-8 py-3.5 bg-[#748660] text-[#0F140A] font-bold uppercase tracking-widest hover:bg-[#60704E] transition-all">
                    {{ __('landing_home.cta_start_wizard') }}
                </a>
                <a href="https://wa.me/201015218548" target="_blank" rel="noopener noreferrer" class="px-8 py-3.5 border border-[#333333] text-zinc-300 hover:text-white font-bold uppercase tracking-widest hover:border-white transition-all">
                    {{ __('landing_home.cta_whatsapp') }}
                </a>
            </div>
        </div>

    </div>

</div>
@endsection
